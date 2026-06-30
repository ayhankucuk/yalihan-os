<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Address;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AddressController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Address::with(['il', 'ilce', 'mahalle'])
            ->latest();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('address', 'LIKE', "%{$search}%")
                    ->orWhere('postal_code', 'LIKE', "%{$search}%")
                    ->orWhereHas('il', function ($q) use ($search) {
                        $q->where('il_adi', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('ilce', function ($q) use ($search) {
                        $q->where('ilce_adi', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('mahalle', function ($q) use ($search) {
                        $q->where('mahalle_adi', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Filter by city
        if ($request->has('il_id') && $request->il_id) {
            $query->where('il_id', $request->il_id);
        }

        // Filter by district
        if ($request->has('ilce_id') && $request->ilce_id) {
            $query->where('ilce_id', $request->ilce_id);
        }

        $addresses = $query->paginate(15);
        $iller = Il::orderBy('il_adi')->get();
        $ilceler = Ilce::when($request->il_id, function ($q) use ($request) {
            return $q->where('il_id', $request->il_id);
        })->orderBy('ilce_adi')->get(); // context7-ignore

        if ($request->expectsJson()) {
            return response()->json($addresses);
        }

        return view('admin.address.index', compact('addresses', 'iller', 'ilceler'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $iller = Il::orderBy('il_adi')->get();
        $ilceler = Ilce::orderBy('ilce_adi')->get();
        $mahalleler = Mahalle::orderBy('mahalle_adi')->get();

        return view('admin.address.create', compact('iller', 'ilceler', 'mahalleler'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, \App\Actions\Address\CreateAddressAction $action)
    {
        $this->authorize('create', Address::class);

        $request->validate([
            'address' => 'required|string|max:500',
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'aktiflik_durumu' => 'required|in:active,inactive',
        ]);

        $address = $action->handle($request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address created successfully',
                'data' => $address->load(['il', 'ilce', 'mahalle']),
            ], 201);
        }

        return redirect()
            ->route('admin.address.index')
            ->with('success', 'Address created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Address $address)
    {
        $address->load(['il', 'ilce', 'mahalle']);

        if (request()->expectsJson()) {
            return response()->json($address);
        }

        return view('admin.address.show', compact('address'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Address $address)
    {
        $iller = Il::orderBy('il_adi')->get();
        $ilceler = Ilce::when($address->il_id, function ($q) use ($address) {
            return $q->where('il_id', $address->il_id);
        })->orderBy('ilce_adi')->get(); // context7-ignore
        $mahalleler = Mahalle::when($address->ilce_id, function ($q) use ($address) {
            return $q->where('ilce_id', $address->ilce_id);
        })->orderBy('mahalle_adi')->get(); // context7-ignore

        return view('admin.address.edit', compact('address', 'iller', 'ilceler', 'mahalleler'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Address $address, \App\Actions\Address\UpdateAddressAction $action)
    {
        $this->authorize('update', $address);

        $request->validate([
            'address' => 'required|string|max:500',
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'aktiflik_durumu' => 'required|in:active,inactive',
        ]);

        $address = $action->handle($address, $request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'data' => $address->fresh(['il', 'ilce', 'mahalle']),
            ]);
        }

        return redirect()
            ->route('admin.address.index')
            ->with('success', 'Address updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address, \App\Actions\Address\DestroyAddressAction $action)
    {
        $this->authorize('delete', $address);

        // Check if address is being used by any properties
        if ($address->properties()->count() > 0) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete address that is being used by properties',
                ], 422);
            }

            return redirect()
                ->route('admin.address.index')
                ->with('error', 'Cannot delete address that is being used by properties');
        }

        $action->handle($address);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
            ]);
        }

        return redirect()
            ->route('admin.address.index')
            ->with('success', 'Address deleted successfully');
    }

    /**
     * Get districts by city (AJAX)
     */
    public function getDistricts(Request $request)
    {
        $ilId = $request->il_id;
        $ilceler = Ilce::where('il_id', $ilId)->orderBy('ilce_adi')->get(); // context7-ignore

        return response()->json($ilceler);
    }

    /**
     * Get neighborhoods by district (AJAX)
     */
    public function getNeighborhoods(Request $request)
    {
        $ilceId = $request->ilce_id;
        $mahalleler = Mahalle::where('ilce_id', $ilceId)->orderBy('mahalle_adi')->get(); // context7-ignore

        return response()->json($mahalleler);
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request, \App\Actions\Address\BulkAddressAction $action)
    {
        $this->authorize('manage', Address::class);

        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'address_ids' => 'required|array',
            'address_ids.*' => 'exists:addresses,id',
        ]);

        $addressIds = $request->address_ids;
        $operation = $request->action;

        $action->handle($addressIds, $operation);

        $message = 'Selected addresses operations completed successfully';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('admin.address.index')
            ->with('success', $message);
    }
}
