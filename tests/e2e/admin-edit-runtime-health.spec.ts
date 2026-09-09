/**
 * Admin listing edit screen runtime health.
 *
 * A successful wizard redirect only proves that a listing was created. This
 * check keeps the edit screen honest: Alpine/Leaflet exceptions and failed
 * application requests are release-blocking browser errors.
 *
 * Evidence label: BROWSER_VERIFIED (when run against an explicit environment)
 */
import { expect, test } from "@playwright/test";

type RuntimeFailure = {
  kind: "pageerror" | "response";
  detail: string;
};

function applicationOrigin(baseURL: string | undefined): string {
  if (!baseURL) {
    throw new Error("EDIT_RUNTIME_CONFIG: Playwright baseURL is required");
  }

  return new URL(baseURL).origin;
}

test.describe("Admin ilan edit runtime health", () => {
  test("TC-GT-11 — edit ekranı uygulama hatası olmadan yüklenmeli", async ({
    page,
    baseURL,
  }) => {
    const origin = applicationOrigin(baseURL);
    const failures: RuntimeFailure[] = [];

    page.on("pageerror", (error) => {
      failures.push({ kind: "pageerror", detail: error.message });
    });

    page.on("response", (response) => {
      if (response.status() < 400) return;

      const url = response.url();
      if (new URL(url).origin !== origin) return;

      failures.push({
        kind: "response",
        detail: `${response.request().method()} ${response.status()} ${url}`,
      });
    });

    // The public/API list can have a different visibility contract from the
    // authenticated admin index. Select a real edit link from the same UI
    // and session the operator uses.
    const indexResponse = await page.goto("/admin/ilanlar", {
      waitUntil: "domcontentloaded",
    });
    expect(indexResponse?.status(), "Admin listing index must load").toBe(200);
    let ids = await page
      .locator('a[href*="/admin/ilanlar/"]')
      .evaluateAll((links) =>
        links
          .map((link) => {
            const pathname = new URL((link as HTMLAnchorElement).href).pathname;
            return pathname.match(/^\/admin\/ilanlar\/(\d+)\/edit$/)?.[1];
          })
          .filter((id): id is string => Boolean(id))
          .map(Number),
      );

    if (ids.length === 0) {
      await page.goto("/admin/ilanlar?tab=drafts", {
        waitUntil: "domcontentloaded",
      });
      ids = await page
        .locator('a[href*="/admin/ilanlar/"]')
        .evaluateAll((links) =>
          links
            .map((link) => {
              const pathname = new URL((link as HTMLAnchorElement).href).pathname;
              return pathname.match(/^\/admin\/ilanlar\/(\d+)\/edit$/)?.[1];
            })
            .filter((id): id is string => Boolean(id))
            .map(Number),
        );
    }
    expect(
      ids.length,
      "At least one admin-visible listing fixture is required",
    ).toBeGreaterThan(0);

    // Index page health is covered separately; only the selected edit page
    // belongs to this test's runtime assertion.
    failures.length = 0;

    let openedListingId: number | null = null;
    for (const id of ids) {
      const response = await page.goto(`/admin/ilanlar/${id}/edit`, {
        waitUntil: "domcontentloaded",
      });
      if (response?.status() !== 200) continue;

      const form = page.locator("#ilan-create-form");
      if (await form.isVisible().catch(() => false)) {
        openedListingId = id;
        break;
      }
    }

    expect(
      openedListingId,
      "A tenant-visible listing must open in the admin edit form",
    ).not.toBeNull();
    await page.waitForLoadState("networkidle").catch(() => undefined);

    await expect(page.locator("#section-location")).toBeVisible();
    await expect(page.locator("#map")).toBeVisible();

    await page.waitForFunction(
      () => Boolean((window as any).VanillaLocationManager?.map || (window as any).mapManager?.map || (document.getElementById('map') as any)?._leaflet_id),
      { timeout: 5000 }
    ).catch(() => undefined);

    const mapDebug = await page.evaluate(() => ({
      vanillaType: typeof (window as any).VanillaLocationManager,
      vanillaHasMap: Boolean((window as any).VanillaLocationManager?.map),
      mapManagerHasMap: Boolean((window as any).mapManager?.map),
      leafletId: (document.getElementById('map') as any)?._leaflet_id ?? null,
      hasL: typeof (window as any).L !== 'undefined'
    }));

    const mapInitialized = Boolean(mapDebug.vanillaHasMap || mapDebug.mapManagerHasMap || mapDebug.leafletId);
    expect(
      mapInitialized,
      `Leaflet map must initialise on the edit screen. Debug: ${JSON.stringify(mapDebug)}`,
    ).toBe(true);

    expect(
      failures,
      `Edit runtime failures for ilan ${openedListingId}: ${failures.map((f) => `[${f.kind}] ${f.detail}`).join(" | ")}`,
    ).toEqual([]);
  });
});
