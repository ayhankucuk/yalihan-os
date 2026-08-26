/**
 * Recovery-C Media Implementation - Browser Certification
 *
 * Test Scope: Step 3 Photo Upload SSOT Architecture
 * Invariant: Alpine photos[] ← wizard:photos-updated event ← Native input.files
 *
 * Certification Requirements:
 * 1. File selection updates all 3 layers synchronously
 * 2. Alpine photo count === Native input.files.length
 * 3. Remove operation maintains SSOT path
 * 4. Re-add operation maintains SSOT path
 * 5. Preview rendering matches photo count
 * 6. Zero console errors during operations
 * 7. DB persistence after final submit
 */

import { test, expect } from "@playwright/test";
import { chromium } from "@playwright/test";
import path from "path";
import fs from "fs";

// Test fixtures - create small test images
const TEST_IMAGE_DIR = path.join(process.cwd(), "tests", "fixtures", "images");
const EVIDENCE_DIR = path.join(process.cwd(), "audits", "recovery-c-evidence");

// Ensure directories exist
if (!fs.existsSync(TEST_IMAGE_DIR)) {
  fs.mkdirSync(TEST_IMAGE_DIR, { recursive: true });
}
if (!fs.existsSync(EVIDENCE_DIR)) {
  fs.mkdirSync(EVIDENCE_DIR, { recursive: true });
}

test.describe("Recovery-C Step 3 Media - SSOT Certification", () => {
  let context;
  let page;
  let consoleErrors = [];

  test.beforeAll(async () => {
    // Create test images if they don't exist
    const testImages = ["test-photo-1.jpg", "test-photo-2.jpg"];
    for (const img of testImages) {
      const imgPath = path.join(TEST_IMAGE_DIR, img);
      if (!fs.existsSync(imgPath)) {
        // Create a minimal valid JPEG (1x1 red pixel)
        const jpegHeader = Buffer.from([
          0xff, 0xd8, 0xff, 0xe0, 0x00, 0x10, 0x4a, 0x46, 0x49, 0x46, 0x00,
          0x01, 0x01, 0x00, 0x00, 0x01, 0x00, 0x01, 0x00, 0x00, 0xff, 0xdb,
          0x00, 0x43, 0x00, 0x08, 0x06, 0x06, 0x07, 0x06, 0x05, 0x08, 0x07,
          0x07, 0x07, 0x09, 0x09, 0x08, 0x0a, 0x0c, 0x14, 0x0d, 0x0c, 0x0b,
          0x0b, 0x0c, 0x19, 0x12, 0x13, 0x0f, 0x14, 0x1d, 0x1a, 0x1f, 0x1e,
          0x1d, 0x1a, 0x1c, 0x1c, 0x20, 0x24, 0x2e, 0x27, 0x20, 0x22, 0x2c,
          0x23, 0x1c, 0x1c, 0x28, 0x37, 0x29, 0x2c, 0x30, 0x31, 0x34, 0x34,
          0x34, 0x1f, 0x27, 0x39, 0x3d, 0x38, 0x32, 0x3c, 0x2e, 0x33, 0x34,
          0x32, 0xff, 0xc0, 0x00, 0x0b, 0x08, 0x00, 0x01, 0x00, 0x01, 0x01,
          0x01, 0x11, 0x00, 0xff, 0xc4, 0x00, 0x14, 0x00, 0x01, 0x00, 0x00,
          0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
          0x00, 0x00, 0x03, 0xff, 0xda, 0x00, 0x08, 0x01, 0x01, 0x00, 0x00,
          0x3f, 0x00, 0x37, 0xff, 0xd9,
        ]);
        fs.writeFileSync(imgPath, jpegHeader);
      }
    }
  });

  test.beforeEach(async ({ browser }) => {
    context = await browser.newContext();
    page = await context.newPage();
    consoleErrors = [];

    // Capture console errors
    page.on("console", (msg) => {
      if (msg.type() === "error") {
        consoleErrors.push(msg.text());
      }
    });

    page.on("pageerror", (error) => {
      consoleErrors.push(`PAGE ERROR: ${error.message}`);
    });

    // Login as admin
    await page.goto("http://localhost/admin/login");
    await page.fill('input[name="email"]', "admin@yalihan.local");
    await page.fill('input[name="password"]', "Password123!");
    await page.click('button[type="submit"]');
    await page.waitForURL("**/admin/dashboard", { timeout: 10000 });

    // Navigate to wizard
    await page.goto("http://localhost/admin/ilanlar/create-wizard");
    await page.waitForLoadState("networkidle");

    // Complete Step 1 (required for Step 3)
    await page.selectOption("#ana_kategori_id", { index: 1 });
    await page.waitForTimeout(500);
    await page.selectOption("#alt_kategori_id", { index: 1 });
    await page.waitForTimeout(500);
    await page.selectOption("#junction_id", { index: 1 });
    await page.fill("#baslik", "Test İlan - Recovery-C Certification");

    // Navigate to Step 3
    await page.click('button:has-text("İleri")');
    await page.waitForTimeout(500);
    await page.click('button:has-text("İleri")');
    await page.waitForLoadState("networkidle");
  });

  test.afterEach(async () => {
    // Take screenshot of final state
    const timestamp = Date.now();
    await page.screenshot({
      path: path.join(EVIDENCE_DIR, `recovery-c-final-${timestamp}.png`),
      fullPage: true,
    });

    await context.close();
  });

  test("TC-RC-01: 2 dosya seçimi - Alpine/Native senkronizasyonu", async () => {
    console.log("🧪 TC-RC-01: Testing 2-file selection SSOT sync...");

    // Initial state: 0 photos
    const initialAlpineCount = await page.evaluate(() => {
      return window.Alpine?.raw($el)?.photos?.length || 0;
    });
    expect(initialAlpineCount).toBe(0);

    // Select 2 files
    const fileInput = await page.locator("#fotograflar");
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
      path.join(TEST_IMAGE_DIR, "test-photo-2.jpg"),
    ]);

    // Wait for Alpine reactive update
    await page.waitForTimeout(1000);

    // Verify Alpine count
    const alpineCount = await page.evaluate(() => {
      const el = document.querySelector('[x-data*="photoWizardStep2"]');
      if (!el) return -1;
      return window.Alpine?.raw(el)?.photos?.length || -1;
    });

    // Verify Native input count
    const nativeCount = await page.evaluate(() => {
      const input = document.getElementById("fotograflar");
      return input?.files?.length || -1;
    });

    // Verify Preview DOM count
    const previewCount = await page
      .locator("#photo-preview-grid > div")
      .count();

    console.log(
      `📊 Counts - Alpine: ${alpineCount}, Native: ${nativeCount}, Preview: ${previewCount}`,
    );

    // Screenshot evidence
    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-01-2-files-selected.png"),
      fullPage: true,
    });

    // Assertions
    expect(alpineCount).toBe(2);
    expect(nativeCount).toBe(2);
    expect(previewCount).toBe(2);
    expect(consoleErrors.length).toBe(0);

    console.log("✅ TC-RC-01 PASS: All 3 layers synchronized");
  });

  test("TC-RC-02: Remove photo - SSOT path validation", async () => {
    console.log("🧪 TC-RC-02: Testing removePhoto() SSOT path...");

    // Add 2 photos first
    const fileInput = await page.locator("#fotograflar");
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
      path.join(TEST_IMAGE_DIR, "test-photo-2.jpg"),
    ]);
    await page.waitForTimeout(1000);

    // Verify initial state: 2 photos
    let counts = await page.evaluate(() => {
      const el = document.querySelector('[x-data*="photoWizardStep2"]');
      const alpineCount = window.Alpine?.raw(el)?.photos?.length || 0;
      const nativeCount =
        document.getElementById("fotograflar")?.files?.length || 0;
      return { alpine: alpineCount, native: nativeCount };
    });
    expect(counts.alpine).toBe(2);
    expect(counts.native).toBe(2);

    // Screenshot before remove
    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-02-before-remove.png"),
      fullPage: true,
    });

    // Remove first photo (hover to show overlay, click remove button)
    await page.locator("#photo-preview-grid > div").first().hover();
    await page.waitForTimeout(300);
    await page
      .locator("#photo-preview-grid > div")
      .first()
      .locator('button:has-text("Kaldır")')
      .click();
    await page.waitForTimeout(1000);

    // Verify after remove: 1 photo
    counts = await page.evaluate(() => {
      const el = document.querySelector('[x-data*="photoWizardStep2"]');
      const alpineCount = window.Alpine?.raw(el)?.photos?.length || 0;
      const nativeCount =
        document.getElementById("fotograflar")?.files?.length || 0;
      const previewCount = document.querySelectorAll(
        "#photo-preview-grid > div",
      ).length;
      return {
        alpine: alpineCount,
        native: nativeCount,
        preview: previewCount,
      };
    });

    console.log(
      `📊 After Remove - Alpine: ${counts.alpine}, Native: ${counts.native}, Preview: ${counts.preview}`,
    );

    // Screenshot after remove
    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-02-after-remove.png"),
      fullPage: true,
    });

    // Assertions
    expect(counts.alpine).toBe(1);
    expect(counts.native).toBe(1);
    expect(counts.preview).toBe(1);
    expect(consoleErrors.length).toBe(0);

    console.log("✅ TC-RC-02 PASS: Remove operation maintains SSOT");
  });

  test("TC-RC-03: Re-add photo - SSOT path validation", async () => {
    console.log("🧪 TC-RC-03: Testing handleFiles() re-add SSOT path...");

    // Add 1 photo first
    const fileInput = await page.locator("#fotograflar");
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
    ]);
    await page.waitForTimeout(1000);

    // Verify initial: 1 photo
    let counts = await page.evaluate(() => {
      const el = document.querySelector('[x-data*="photoWizardStep2"]');
      return {
        alpine: window.Alpine?.raw(el)?.photos?.length || 0,
        native: document.getElementById("fotograflar")?.files?.length || 0,
      };
    });
    expect(counts.alpine).toBe(1);
    expect(counts.native).toBe(1);

    // Screenshot before re-add
    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-03-before-readd.png"),
      fullPage: true,
    });

    // Re-add another photo (append, not replace)
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
      path.join(TEST_IMAGE_DIR, "test-photo-2.jpg"),
    ]);
    await page.waitForTimeout(1000);

    // Verify after re-add: 2 photos
    counts = await page.evaluate(() => {
      const el = document.querySelector('[x-data*="photoWizardStep2"]');
      return {
        alpine: window.Alpine?.raw(el)?.photos?.length || 0,
        native: document.getElementById("fotograflar")?.files?.length || 0,
        preview: document.querySelectorAll("#photo-preview-grid > div").length,
      };
    });

    console.log(
      `📊 After Re-add - Alpine: ${counts.alpine}, Native: ${counts.native}, Preview: ${counts.preview}`,
    );

    // Screenshot after re-add
    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-03-after-readd.png"),
      fullPage: true,
    });

    // Assertions
    expect(counts.alpine).toBe(2);
    expect(counts.native).toBe(2);
    expect(counts.preview).toBe(2);
    expect(consoleErrors.length).toBe(0);

    console.log("✅ TC-RC-03 PASS: Re-add operation maintains SSOT");
  });

  test("TC-RC-04: Preview count visibility", async () => {
    console.log("🧪 TC-RC-04: Testing x-show preview rendering...");

    // Initial: preview should be hidden
    const initialVisible = await page
      .locator("#photo-preview-grid")
      .isVisible();
    expect(initialVisible).toBe(false);

    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-04-preview-hidden.png"),
      fullPage: true,
    });

    // Add 2 photos
    const fileInput = await page.locator("#fotograflar");
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
      path.join(TEST_IMAGE_DIR, "test-photo-2.jpg"),
    ]);
    await page.waitForTimeout(1000);

    // Preview should now be visible
    const afterVisible = await page.locator("#photo-preview-grid").isVisible();
    expect(afterVisible).toBe(true);

    // Count preview items
    const previewCount = await page
      .locator("#photo-preview-grid > div")
      .count();
    expect(previewCount).toBe(2);

    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-04-preview-visible.png"),
      fullPage: true,
    });

    console.log("✅ TC-RC-04 PASS: Preview renders correctly");
  });

  test("TC-RC-05: Console error monitoring", async () => {
    console.log(
      "🧪 TC-RC-05: Monitoring console errors during full workflow...",
    );

    // Full workflow: add → remove → re-add
    const fileInput = await page.locator("#fotograflar");

    // Add 2 photos
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
      path.join(TEST_IMAGE_DIR, "test-photo-2.jpg"),
    ]);
    await page.waitForTimeout(1000);

    // Remove first
    await page.locator("#photo-preview-grid > div").first().hover();
    await page.waitForTimeout(300);
    await page
      .locator("#photo-preview-grid > div")
      .first()
      .locator('button:has-text("Kaldır")')
      .click();
    await page.waitForTimeout(1000);

    // Re-add
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
      path.join(TEST_IMAGE_DIR, "test-photo-2.jpg"),
    ]);
    await page.waitForTimeout(1000);

    // Check console errors
    console.log(`📊 Console Errors: ${consoleErrors.length}`);
    if (consoleErrors.length > 0) {
      console.log("❌ Errors found:");
      consoleErrors.forEach((err, i) => console.log(`  ${i + 1}. ${err}`));
    }

    // Write error log
    fs.writeFileSync(
      path.join(EVIDENCE_DIR, "tc-rc-05-console-errors.txt"),
      consoleErrors.length === 0
        ? "✅ No console errors detected"
        : `❌ Errors:\n${consoleErrors.join("\n")}`,
    );

    expect(consoleErrors.length).toBe(0);
    console.log("✅ TC-RC-05 PASS: Zero console errors");
  });

  test("TC-RC-06: Full certification report", async () => {
    console.log("🧪 TC-RC-06: Generating full certification report...");

    const report = {
      timestamp: new Date().toISOString(),
      test_suite: "Recovery-C Media Step 3 SSOT Certification",
      invariant:
        "Alpine photos[] ← wizard:photos-updated event ← Native input.files",
      tests: [],
    };

    // Test 1: Add 2 photos
    const fileInput = await page.locator("#fotograflar");
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
      path.join(TEST_IMAGE_DIR, "test-photo-2.jpg"),
    ]);
    await page.waitForTimeout(1000);

    const state1 = await page.evaluate(() => {
      const el = document.querySelector('[x-data*="photoWizardStep2"]');
      return {
        alpine: window.Alpine?.raw(el)?.photos?.length || 0,
        native: document.getElementById("fotograflar")?.files?.length || 0,
        preview: document.querySelectorAll("#photo-preview-grid > div").length,
      };
    });

    report.tests.push({
      name: "Add 2 photos",
      alpine_count: state1.alpine,
      native_count: state1.native,
      preview_count: state1.preview,
      synchronized:
        state1.alpine === state1.native && state1.native === state1.preview,
      pass: state1.alpine === 2 && state1.native === 2 && state1.preview === 2,
    });

    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-06-step1-add2.png"),
    });

    // Test 2: Remove 1 photo
    await page.locator("#photo-preview-grid > div").first().hover();
    await page.waitForTimeout(300);
    await page
      .locator("#photo-preview-grid > div")
      .first()
      .locator('button:has-text("Kaldır")')
      .click();
    await page.waitForTimeout(1000);

    const state2 = await page.evaluate(() => {
      const el = document.querySelector('[x-data*="photoWizardStep2"]');
      return {
        alpine: window.Alpine?.raw(el)?.photos?.length || 0,
        native: document.getElementById("fotograflar")?.files?.length || 0,
        preview: document.querySelectorAll("#photo-preview-grid > div").length,
      };
    });

    report.tests.push({
      name: "Remove 1 photo (2→1)",
      alpine_count: state2.alpine,
      native_count: state2.native,
      preview_count: state2.preview,
      synchronized:
        state2.alpine === state2.native && state2.native === state2.preview,
      pass: state2.alpine === 1 && state2.native === 1 && state2.preview === 1,
    });

    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-06-step2-remove1.png"),
    });

    // Test 3: Re-add photo
    await fileInput.setInputFiles([
      path.join(TEST_IMAGE_DIR, "test-photo-1.jpg"),
      path.join(TEST_IMAGE_DIR, "test-photo-2.jpg"),
    ]);
    await page.waitForTimeout(1000);

    const state3 = await page.evaluate(() => {
      const el = document.querySelector('[x-data*="photoWizardStep2"]');
      return {
        alpine: window.Alpine?.raw(el)?.photos?.length || 0,
        native: document.getElementById("fotograflar")?.files?.length || 0,
        preview: document.querySelectorAll("#photo-preview-grid > div").length,
      };
    });

    report.tests.push({
      name: "Re-add photo (1→2)",
      alpine_count: state3.alpine,
      native_count: state3.native,
      preview_count: state3.preview,
      synchronized:
        state3.alpine === state3.native && state3.native === state3.preview,
      pass: state3.alpine === 2 && state3.native === 2 && state3.preview === 2,
    });

    await page.screenshot({
      path: path.join(EVIDENCE_DIR, "tc-rc-06-step3-readd.png"),
      fullPage: true,
    });

    // Test 4: Console errors
    report.tests.push({
      name: "Console error check",
      error_count: consoleErrors.length,
      errors: consoleErrors,
      pass: consoleErrors.length === 0,
    });

    // Summary
    report.summary = {
      total_tests: report.tests.length,
      passed: report.tests.filter((t) => t.pass).length,
      failed: report.tests.filter((t) => !t.pass).length,
      all_synchronized: report.tests.every((t) => t.synchronized !== false),
      certification_status: report.tests.every((t) => t.pass) ? "PASS" : "FAIL",
    };

    // Write report
    fs.writeFileSync(
      path.join(EVIDENCE_DIR, "recovery-c-certification-report.json"),
      JSON.stringify(report, null, 2),
    );

    // Write human-readable markdown
    const markdown = `# Recovery-C Media Certification Report

**Date:** ${report.timestamp}
**Invariant:** ${report.invariant}
**Status:** ${report.summary.certification_status}

## Test Results

${report.tests
  .map(
    (t, i) => `
### Test ${i + 1}: ${t.name}

- Alpine Count: ${t.alpine_count}
- Native Count: ${t.native_count}
- Preview Count: ${t.preview_count || "N/A"}
- Synchronized: ${t.synchronized !== false ? "✅ Yes" : "❌ No"}
- Result: ${t.pass ? "✅ PASS" : "❌ FAIL"}
`,
  )
  .join("\n")}

## Summary

- Total Tests: ${report.summary.total_tests}
- Passed: ${report.summary.passed}
- Failed: ${report.summary.failed}
- All Layers Synchronized: ${report.summary.all_synchronized ? "✅ Yes" : "❌ No"}

## Certification

**Status: ${report.summary.certification_status}**

${
  report.summary.certification_status === "PASS"
    ? "✅ Recovery-C Media implementation maintains SSOT invariant across all operations."
    : "❌ Recovery-C Media implementation has synchronization issues."
}
`;

    fs.writeFileSync(
      path.join(EVIDENCE_DIR, "recovery-c-certification-report.md"),
      markdown,
    );

    console.log("📄 Report written to:", EVIDENCE_DIR);
    console.log(
      `🎯 Certification Status: ${report.summary.certification_status}`,
    );

    expect(report.summary.certification_status).toBe("PASS");
    console.log("✅ TC-RC-06 PASS: Full certification complete");
  });
});
