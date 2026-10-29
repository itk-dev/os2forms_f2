import { test, expect } from "@playwright/test";

// We want run and complete the administrator stuff before running the form-builder stuff.
// https://playwright.dev/docs/test-parallel
test.describe.configure({ mode: "serial" });

import dotenv from "dotenv";
import path from "path";
import * as child_process from "node:child_process";
dotenv.config({ path: path.resolve(__dirname, "../.env.local") });

test("webform submission", async ({ page }) => {
  const locale = "da";
  const context = "webform-submission";
  let screenshotCounter = 0;

  const screenshot = async (
    name: string,
    selector: string = ".page-wrapper",
  ) => {
    const filePath = path.join(
      "/docs/user-manual/da/images/generated/",
      context,
      String(screenshotCounter++).padStart(3, "0") + "-" + name + ".png",
    );
    await page.locator(selector).screenshot({ path: filePath });
  };

  const drush = async (args: string[]) => {
    return page.request.post("/os2forms-test-playwright/exec/drush", {
      data: args,
      timeout: 4000,
    });
  };

  const goto = async (url: string) => {
    url = `/${locale}/${url}`;
    console.debug(`going to ${url} …`);
    return page.goto(url);
  };

  await drush([
    'sql:query', [
      "TRUNCATE advancedqueue",
      "TRUNCATE webform_submission_log",
      "TRUNCATE webform_submission_data",
      "TRUNCATE webform_submission",
      "ALTER TABLE webform_submission AUTO_INCREMENT = 1",
      "UPDATE webform SET next_serial = 1",
    ].join(';')
  ]);

  await goto("webform/os2forms_f2_ex_001");
  await screenshot("form-empty");

  await page
    .locator('input[data-drupal-selector="edit-name"]')
    .fill("Anders And");
  await screenshot("form-filled");

  await page.locator('input[data-drupal-selector="edit-submit"]').click();
  await screenshot("form-submitted");

  await goto(
    "admin/structure/webform/manage/os2forms_f2_ex_001/submission/1/log",
  );
  await screenshot("submission-log");

  await drush(["advancedqueue:queue:process", "os2forms_f2"]);

  await goto(
    "admin/structure/webform/manage/os2forms_f2_ex_001/submission/1/log",
  );
  await screenshot("submission-log-after-queue-processed");
});
