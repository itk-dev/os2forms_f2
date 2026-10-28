import { test, expect } from "@playwright/test";

// We want run and complete the administrator stuff before running the form-builder stuff.
// https://playwright.dev/docs/test-parallel
test.describe.configure({ mode: "serial" });

import dotenv from "dotenv";
import path from "path";
dotenv.config({ path: path.resolve(__dirname, "../.env.local") });

const submitSelector = 'input[data-drupal-selector="edit-submit"]';

test("user manual images – administrator (da)", async ({ page }) => {
  async function takeScreenshot(path, locator = "main") {
    const imagePath =
      "/docs/user-manual/da/images/generated/administrator/" + path;
    await page.locator(locator).screenshot({ path: imagePath });
    console.log(imagePath);
  }

  await page.goto("da/admin/os2forms_f2/settings");
  await page.locator('[name="f2_api[uri]"]').fill("");
  await page.locator('[name="f2_api[username]"]').fill("");
  await page.locator('[name="f2_api[secret]"]').fill("");
  await page.locator('[name="f2_api[f2_username]"]').fill("");
  await page.locator('[name="general[queue]"]').selectOption(null);
  await page.locator('[name="f2_api[uri]"]').focus();
  await page.locator('[name="f2_api[uri]"]').blur();
  await takeScreenshot("f2-settings-empty.png", ".page-wrapper");

  await page.goto("da/admin/os2forms_f2/settings");
  await page.locator('[name="f2_api[uri]"]').fill("https://f2.example.com");
  await page.locator('[name="f2_api[username]"]').fill("api-user");
  await page.locator('[name="f2_api[secret]"]').fill("api-$ecr3t");
  await page.locator('[name="f2_api[f2_username]"]').fill("f2-user");
  await page.locator('[name="general[queue]"]').selectOption("os2forms_f2");
  await page.locator('[name="f2_api[uri]"]').focus();
  await page.locator('[name="f2_api[uri]"]').blur();
  await takeScreenshot("f2-settings-filled.png");

  await page.locator(submitSelector).click();
  await takeScreenshot("f2-settings-saved.png");

  await page
    .locator('input[data-drupal-selector="edit-action-ping-api"]')
    .click();
  await takeScreenshot(
    "f2-settings-api-pinged-error.png",
    ".messages__wrapper",
  );

  // Refill with proper values
  await page.locator('[name="f2_api[uri]"]').fill(process.env.F2_API_URI);
  await page
    .locator('[name="f2_api[username]"]')
    .fill(process.env.F2_API_USERNAME);
  await page.locator('[name="f2_api[secret]"]').fill(process.env.F2_API_SECRET);
  await page
    .locator('[name="f2_api[f2_username]"]')
    .fill(process.env.F2_API_F2_USERNAME);
  await page.locator('[name="f2_api[uri]"]').focus();
  await page.locator('[name="f2_api[uri]"]').blur();
  await page.locator(submitSelector).click();

  await page
    .locator('input[data-drupal-selector="edit-action-ping-api"]')
    .click();
  await takeScreenshot(
    "f2-settings-api-pinged-success.png",
    ".messages__wrapper",
  );
});

test("user manual images – form-builder (da)", async ({ page }) => {
  async function takeScreenshot(path, locator = "main") {
    const imagePath =
      "/docs/user-manual/da/images/generated/form-builder/" + path;
    await page.locator(locator).screenshot({ path: imagePath });
    console.log(imagePath);
  }

  // Add handler
  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_000/handlers",
  );
  await page
    .locator(
      '[data-drupal-link-system-path="admin/structure/webform/manage/os2forms_f2_ex_000/handlers/add"]',
    )
    .click();
  await page.locator(".form-element--type-search").fill("F2");
  // Wait for the AJAX search
  await page.waitForTimeout(1000);
  await takeScreenshot("webform-handler-select-f2.png", ".webform-ui-dialog");

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_000/handlers/add/os2forms_f2_f2",
  );
  await takeScreenshot(
    "webform-handler-add-general.png",
    "#webform-handler-form",
  );
  await takeScreenshot(
    "webform-handler-add-general-archive-settings.png",
    "#edit-settings-os2forms-f2-f2",
  );

  // Archive on case
  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_000/handlers/add/os2forms_f2_f2",
  );

  await page.locator('[name="label"]').fill("F2 – arkivér på sag");
  // Unhide and fill machine name field.
  await page.evaluate(
    'document.querySelector(".form-type-machine-name").classList.remove("hidden")',
  );
  await page.locator('[name="handler_id"]').fill("f2_arkiver_paa_sag");
  await page
    .locator('[name="settings[os2forms_f2_f2][archive][attachment_element]"]')
    .selectOption("attachment");
  await page
    .locator('[name="settings[os2forms_f2_f2][archive][document_title]"]')
    .fill("[webform_submission:label] ([site:url])");
  await page
    .locator('[name="settings[os2forms_f2_f2][archive][archive_target]"]')
    .focus();

  await takeScreenshot(
    "webform-handler-add-general-archive-settings-filled.png",
    "#edit-settings-os2forms-f2-f2",
  );

  await page
    .locator('[name="settings[os2forms_f2_f2][archive][archive_target]"]')
    .selectOption("case");
  await takeScreenshot(
    "webform-handler-add-archive-on-case.png",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-case",
  );

  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_case][case_identifier]"]',
    )
    .fill(process.env.F2_CASE_IDENTIFIER_INVALID);
  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_case][matter_title]"]',
    )
    .fill("[webform_submission:label]");
  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_case][matter_type]"]',
    )
    .selectOption("Internal");
  await takeScreenshot(
    "webform-handler-add-archive-on-case-filled.png",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-case",
  );

  await page.locator(submitSelector).click();
  await takeScreenshot(
    "webform-handler-add-archive-on-case-filled-invalid-case-identifier.png",
    ".messages__wrapper",
  );

  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_case][case_identifier]"]',
    )
    .fill(process.env.F2_CASE_IDENTIFIER_VALID);
  await page.locator(submitSelector).click();
  await takeScreenshot(
    "webform-handler-add-archive-on-case-filled-valid-case-identifier.png",
    ".messages__wrapper",
  );

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_000/handlers",
  );
  await takeScreenshot("webform-handler-added.png", ".page-wrapper");

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_000/handlers/f2_arkiver_paa_sag/edit",
  );
  await takeScreenshot(
    "webform-handler-archive-on-case-case-details.png",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-case",
  );

  // Archive on matter
  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_000/handlers/add/os2forms_f2_f2",
  );

  await page.locator('[name="label"]').fill("F2 – arkivér på akt");
  // Unhide and fill machine name field.
  await page.evaluate(
    'document.querySelector(".form-type-machine-name").classList.remove("hidden")',
  );
  await page.locator('[name="handler_id"]').fill("f2_arkiver_paa_akt");
  await page
    .locator('[name="settings[os2forms_f2_f2][archive][attachment_element]"]')
    .selectOption("attachment");
  await page
    .locator('[name="settings[os2forms_f2_f2][archive][document_title]"]')
    .fill("[webform_submission:label] ([site:url])");
  await page
    .locator('[name="settings[os2forms_f2_f2][archive][archive_target]"]')
    .focus();

  // await takeScreenshot('webform-handler-add-general-archive-settings-filled.png', '#edit-settings-os2forms-f2-f2');

  await page
    .locator('[name="settings[os2forms_f2_f2][archive][archive_target]"]')
    .selectOption("matter");
  await takeScreenshot(
    "webform-handler-add-archive-on-matter.png",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-matter",
  );

  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_matter][matter_identifier]"]',
    )
    .fill(process.env.F2_MATTER_IDENTIFIER_INVALID);
  await takeScreenshot(
    "webform-handler-add-archive-on-matter-filled.png",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-matter",
  );

  await page.locator(submitSelector).click();
  await takeScreenshot(
    "webform-handler-add-archive-on-matter-filled-invalid-matter-identifier.png",
    ".messages__wrapper",
  );

  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_matter][matter_identifier]"]',
    )
    .fill(process.env.F2_MATTER_IDENTIFIER_VALID);
  await page.locator(submitSelector).click();
  await takeScreenshot(
    "webform-handler-add-archive-on-matter-filled-valid-matter-identifier.png",
    ".messages__wrapper",
  );

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_000/handlers",
  );
  await takeScreenshot("webform-handler-added-2.png", ".page-wrapper");

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_000/handlers/f2_arkiver_paa_akt/edit",
  );
  await takeScreenshot(
    "webform-handler-archive-on-matter-matter-details.png",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-matter",
  );

  // Existing handler

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_001/handlers/f2_archive_on_case/edit",
  );
  await takeScreenshot(
    "webform-handler-archive-on-case.png",
    "#edit-settings-os2forms-f2-f2",
  );
  await takeScreenshot(
    "webform-handler-archive-on-case-case.png",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-case",
  );

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_001/handlers/f2_archive_on_matter/edit",
  );
  await takeScreenshot(
    "webform-handler-archive-on-matter.png",
    "#edit-settings-os2forms-f2-f2",
  );
  await takeScreenshot(
    "webform-handler-archive-on-matter-matter.png",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-matter",
  );

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_001/handlers",
  );
  await takeScreenshot("webform-handlers.png", "#webform-handlers");

  await page.goto(
    "da/admin/structure/webform/manage/os2forms_f2_ex_001/handlers/f2_archive_on_case/edit",
  );
  await page.getByRole("tab", { name: "Avanceret" }).click();
  await expect(page.getByText("Yderligere indstillinger")).toBeVisible();
  await page.getByText("Yderligere indstillinger").click();
  await page.getByText("Yderligere indstillinger").blur();
  await takeScreenshot(
    "webform-handler-add-advanced.png",
    "#webform-handler-form",
  );
});
