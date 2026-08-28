<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Payment;
use App\Models\PropertyReservation;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * CheckoutController — Checkout / Ödeme Akışı
 *
 * CHECKOUT/ÖDEME AKIŞI — IMPLEMENTATION
 *
 * Ödeme sağlayıcı entegrasyonu YOK — mock / manuel onay akışı.
 * İnce controller: tüm iş mantığı CheckoutService'e delege edilir.
 *
 * NOT: Route model binding (Ilan $ilan) yerine int $ilanId + manual resolve
 * kullanılır. Sebep: web grubunda SubstituteBindings middleware'i
 * tenant.context'ten ÖNCE çalışır; bu yüzden TenantScope henüz doğru
 * tenant ile set edilmemişken Ilan resolve edilir ve 404 dönebilir.
 * Manuel resolve ile global scope'ları kontrol altında tutuyoruz.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {
        $this->middleware('can:manage-ilanlar');
    }

    /**
     * Ilan'ı global scope'lar olmadan resolve eder, sonra tenant kontrolü yapar.
     */
    private function resolveIlan(int $ilanId): Ilan
    {
        $ilan = Ilan::withoutGlobalScopes()->find($ilanId);

        if (!$ilan) {
            abort(404, 'İlan bulunamadı.');
        }

        $this->guardTenantAccess($ilan);

        return $ilan;
    }

    /**
     * Rezervasyonu global scope'lar olmadan resolve eder, ilana ait olduğunu doğrular.
     */
    private function resolveReservation(Ilan $ilan, int $reservationId): PropertyReservation
    {
        $reservation = PropertyReservation::withoutGlobalScopes()->find($reservationId);

        if (!$reservation) {
            abort(404, 'Rezervasyon bulunamadı.');
        }

        $this->guardReservationBelongsToIlan($ilan, $reservation);

        return $reservation;
    }

    /**
     * Checkout sayfası — rezervasyon özeti + ödeme formu + ödeme geçmişi.
     */
    public function show(int $ilanId, int $reservationId)
    {
        $ilan = $this->resolveIlan($ilanId);
        $reservation = $this->resolveReservation($ilan, $reservationId);

        $data = $this->checkoutService->getCheckoutData($reservation);

        return view('admin.ilanlar.checkout.index', [
            'ilan' => $ilan,
            'reservation' => $reservation,
            'totalAmount' => $data['total_amount'],
            'currency' => $data['currency'],
            'payments' => $data['payments'],
            'paidTotal' => $data['paid_total'],
            'outstanding' => $data['outstanding'],
            'isFullyPaid' => $data['is_fully_paid'],
        ]);
    }

    /**
     * Yeni ödeme kaydı oluştur (pending).
     */
    public function store(int $ilanId, int $reservationId)
    {
        $ilan = $this->resolveIlan($ilanId);
        $reservation = $this->resolveReservation($ilan, $reservationId);

        $validated = request()->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'payment_method' => 'required|string|in:kart,eft,havale,nakit,mock',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        try {
            $payment = $this->checkoutService->recordPayment(
                $reservation,
                $validated,
                auth()->id()
            );

            return redirect()
                ->route('admin.ilanlar.checkout.show', [$ilan, $reservation])
                ->with('success', "Ödeme kaydı oluşturuldu (#{$payment->id}) — onay bekliyor.");
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.ilanlar.checkout.show', [$ilan, $reservation])
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Ödemeyi onayla (manuel onay akışı).
     */
    public function approve(int $ilanId, int $reservationId, int $paymentId)
    {
        $ilan = $this->resolveIlan($ilanId);
        $reservation = $this->resolveReservation($ilan, $reservationId);

        $payment = Payment::withoutGlobalScopes()->find($paymentId);
        if (!$payment) {
            abort(404, 'Ödeme bulunamadı.');
        }
        $this->guardPaymentBelongsTo($reservation, $payment);

        try {
            $this->checkoutService->approvePayment($payment, auth()->id());

            return redirect()
                ->route('admin.ilanlar.checkout.show', [$ilan, $reservation])
                ->with('success', "Ödeme onaylandı (#{$payment->id}).");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Checkout approve failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return redirect()
                ->route('admin.ilanlar.checkout.show', [$ilan, $reservation])
                ->with('error', 'Ödeme onaylanamadı: ' . $e->getMessage());
        }
    }

    /**
     * Ödemeyi başarısız olarak işaretle.
     */
    public function fail(int $ilanId, int $reservationId, int $paymentId)
    {
        $ilan = $this->resolveIlan($ilanId);
        $reservation = $this->resolveReservation($ilan, $reservationId);

        $payment = Payment::withoutGlobalScopes()->find($paymentId);
        if (!$payment) {
            abort(404, 'Ödeme bulunamadı.');
        }
        $this->guardPaymentBelongsTo($reservation, $payment);

        $validated = request()->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $this->checkoutService->failPayment(
                $payment,
                $validated['reason'] ?? null,
                auth()->id()
            );

            return redirect()
                ->route('admin.ilanlar.checkout.show', [$ilan, $reservation])
                ->with('success', "Ödeme başarısız olarak işaretlendi (#{$payment->id}).");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Checkout fail failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return redirect()
                ->route('admin.ilanlar.checkout.show', [$ilan, $reservation])
                ->with('error', 'Ödeme işaretlenemedi: ' . $e->getMessage());
        }
    }

    /**
     * 🛡️ Tenant izolasyonu: ilan, kimlik doğrulanmış kullanıcının tenant'ına ait olmalı.
     */
    private function guardTenantAccess(Ilan $ilan): void
    {
        $user = auth()->user();

        if (!$user || empty($user->tenant_id) || (int) $ilan->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Bu ilana erişim yetkiniz yok.');
        }
    }

    /**
     * Rezervasyonun ilana ait olduğunu doğrula.
     */
    private function guardReservationBelongsToIlan(Ilan $ilan, PropertyReservation $reservation): void
    {
        if ((int) $reservation->property_id !== (int) $ilan->id) {
            abort(404, 'Rezervasyon bu ilana ait değil.');
        }
    }

    /**
     * Ödemenin rezervasyona ait olduğunu doğrula.
     */
    private function guardPaymentBelongsTo(PropertyReservation $reservation, Payment $payment): void
    {
        if ((int) $payment->reservation_id !== (int) $reservation->id) {
            abort(404, 'Ödeme bu rezervasyona ait değil.');
        }
    }
}
