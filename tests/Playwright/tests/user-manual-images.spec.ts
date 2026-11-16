import { test, expect } from "@playwright/test";
import { DrupalPage } from "./drupal-page.ts";

// We want run and complete the administrator stuff before running the form-builder stuff.
// https://playwright.dev/docs/test-parallel
test.describe.configure({ mode: "serial" });

import dotenv from "dotenv";
import path from "path";
dotenv.config({ path: path.resolve(__dirname, "../.env.local") });

test("xxxxxxxxxx", async ({ page }) => {
  const drupalPage = new DrupalPage(page);

  await drupalPage.goToPath("/admin/config/system/cron");

  await page.addStyleTag({
    url: "http://web/modules/custom/os2forms_f2/modules/os2forms_f2_test/css/module.css",
  });
  await page.addScriptTag({
    type: "module",
    content: `
    import { highlightElement } from "http://web/modules/custom/os2forms_f2/modules/os2forms_f2_test/js/module.js";
    highlightElement({
      selector: '[data-drupal-selector="edit-run"] + p',
      type: 'rectangle',
      offset: 5,
    })
    `,
  });

  await drupalPage.takeScreenshot("cron-before");
  const response = await drupalPage.drush(["core:cron"]);
  await drupalPage.goToPath("/admin/config/system/cron");
  await drupalPage.takeScreenshot("cron-after");

  await page.addStyleTag({
    url: "http://web/modules/custom/os2forms_f2/modules/os2forms_f2_test/css/module.css",
  });
  await page.addScriptTag({
    type: "module",
    content: `
    import { revealMousePointer } from "http://web/modules/custom/os2forms_f2/modules/os2forms_f2_test/js/module.js";
    revealMousePointer({
      selector: '[data-drupal-selector="edit-run"]',
      // position: ['left'],
    })
    `,
  });
  await drupalPage.takeScreenshot("cron-mouse");

  await page.addScriptTag({
    type: "module",
    content: `
    import { expandSelect } from "http://web/modules/custom/os2forms_f2/modules/os2forms_f2_test/js/module.js";
    expandSelect({
      selector: '[data-drupal-selector="edit-interval"]',
      hover: '12 timer',
    })
    `,
  });
  await drupalPage.takeScreenshot("cron-interval");
});

const submitSelector = 'input[data-drupal-selector="edit-submit"]';

test("user manual images – administrator (da)", async ({ page }) => {
  const context = "administrator";
  const locale = "da";
  let screenshotCounter = 0;

  // @todo Do something about this code duplication!

  const goto = async (url: string) => {
    url = `/${locale}/${url}`;
    console.debug(`
going to ${url} …`);
    return page.goto(url);
  };

  const screenshot = async (
    name: string,
    selector: string = "main",
    hideHeader: bool = true,
  ) => {
    const filePath = path.join(
      `/docs/user-manual/${locale}/images/generated/`,
      context,
      // String(screenshotCounter++).padStart(3, "0") + "-" +
      name + ".png",
    );
    // Allow floating header to fall into place
    await page.waitForTimeout(2000);
    const pagePath = page.url().replace(process.env.BASEURL, "");
    console.debug(`
screenshooting ${selector}
            on ${pagePath}
          into ${filePath}`);
    // https://playwright.dev/docs/api/class-page#page-screenshot
    return page.locator(selector).screenshot({
      path: filePath,
      animations: "disabled",
      style: `
header.region-sticky--is-sticky,
header.region-sticky--is-sticky + .sticky-shadow {
  display: none;
}
`,
    });
  };

  const drush = async (args: string[]) => {
    return page.request.post("/os2forms-test-playwright/exec/drush", {
      data: args,
      timeout: 4000,
    });
  };

  const vcrRecord = async (cassette: string) => {
    await page.request.post("/os2forms-test-playwright/vcr/configure", {
      data: {
        cassette_path: "../tests/vcr-cassettes/",
      },
      timeout: 4000,
    });

    return page.request.post("/os2forms-test-playwright/vcr/record", {
      data: {
        cassette,
      },
      timeout: 4000,
    });
  };

  const vcrEject = async (cassette: string) => {
    return page.request.post("/os2forms-test-playwright/vcr/eject", {
      timeout: 4000,
    });
  };

  await goto("admin/os2forms_f2/settings");
  await page.locator('[name="f2_api[uri]"]').fill("");
  await page.locator('[name="f2_api[username]"]').fill("");
  await page.locator('[name="f2_api[secret]"]').fill("");
  await page.locator('[name="f2_api[f2_username]"]').fill("");
  await page.locator('[name="general[queue]"]').selectOption(null);
  await page.locator('[name="f2_api[uri]"]').focus();
  await page.locator('[name="f2_api[uri]"]').blur();
  await screenshot("f2-settings-empty");
  await screenshot("f2-settings-empty-f2-api", "#edit-f2-api", true);
  await screenshot("f2-settings-empty-general", "#edit-general", true);

  const selector = '[name="general[queue]"]';
  const element = page.locator(selector);
  // await element.focus();
  // const boundingBox = await element.boundingBox();
  // await page.mouse.move(boundingBox.x + boundingBox.width/2, boundingBox.y - boundingBox.height/2 + 50);

  console.debug(
    await page.addStyleTag({
      url: "http://web/modules/custom/os2forms_f2/modules/os2forms_f2_test/css/styles.css",
    }),
    await page.addScriptTag({
      url: "http://web/modules/custom/os2forms_f2/modules/os2forms_f2_test/js/script.js",
    }),
  );

  // https://playwright.dev/docs/evaluating
  console.debug(
    await page.evaluate((data) => os2forms_f2_test.expandSelect(data), {
      selector,
      hover: "os2forms_f2", // Hover over item by text, value or index
      mousePointer: "default", // Show mouse pointer over hovered element
    }),
  );

  // console.debug(
  //   await page.evaluate(data => os2forms_f2_test.revealMousePointer(data), {
  //     selector,
  //     x: boundingBox.x + boundingBox.width/2,
  //     y: boundingBox.y - boundingBox.height/2 + 50,
  //   })
  // );

  await screenshot("f2-settings-empty", ".page-wrapper");

  await goto("admin/os2forms_f2/settings");
  await page.locator('[name="f2_api[uri]"]').fill("https://f2.example.com");
  await page.locator('[name="f2_api[username]"]').fill("api-user");
  await page.locator('[name="f2_api[secret]"]').fill("api-$ecr3t");
  await page.locator('[name="f2_api[f2_username]"]').fill("f2-user");

  await page.locator('[name="general[archive_targets][case]"]').check();
  await page
    .locator('[name="general[archive_target_case][matter_type_default_value]"]')
    .selectOption("Internal");
  await page
    .locator(
      '[name="general[archive_target_case][matter_status_default_value]"]',
    )
    .selectOption("Closed");
  await page
    .locator(
      '[name="general[archive_target_case][matter_access_level_default_value]"]',
    )
    .selectOption("All");
  await page
    .locator(
      '[name="general[archive_target_case][matter_archived_default_value]"]',
    )
    .check();

  await page.locator('[name="general[queue]"]').selectOption("os2forms_f2");

  await page.locator('[name="f2_api[uri]"]').focus();
  await page.locator('[name="f2_api[uri]"]').blur();

  await screenshot("f2-settings-filled");
  await screenshot("f2-settings-filled-f2-api", "#edit-f2-api", true);
  await screenshot("f2-settings-filled-general", "#edit-general", true);

  await page.locator(submitSelector).click();
  await screenshot("f2-settings-saved");

  await page
    .locator('input[data-drupal-selector="edit-action-ping-api"]')
    .click();
  await screenshot("f2-settings-api-pinged-error", ".messages__wrapper");

  await vcrRecord(context + "-f2-settings");

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
  await screenshot("f2-settings-api-pinged-success", ".messages__wrapper");

  await vcrEject();
});

test("user manual images – form-builder (da)", async ({ page }) => {
  const context = "form-builder";
  const locale = "da";
  let screenshotCounter = 0;

  // @todo Do something about this code duplication!

  const goto = async (url: string) => {
    url = `/${locale}/${url}`;
    console.debug(`
going to ${url} …`);
    return page.goto(url);
  };

  const screenshot = async (name: string, selector: string = "main") => {
    const filePath = path.join(
      `/docs/user-manual/${locale}/images/generated/`,
      context,
      // String(screenshotCounter++).padStart(3, "0") + "-" +
      name + ".png",
    );
    const pagePath = page.url().replace(process.env.BASEURL, "");
    console.debug(`
screenshooting ${selector}
            on ${pagePath}
          into ${filePath}`);
    return page.locator(selector).screenshot({ path: filePath });
  };

  const drush = async (args: string[]) => {
    return page.request.post("/os2forms-test-playwright/exec/drush", {
      data: args,
      timeout: 4000,
    });
  };

  const vcrRecord = async (cassette: string) => {
    await page.request.post("/os2forms-test-playwright/vcr/configure", {
      data: {
        cassette_path: "../tests/vcr-cassettes/",
      },
      timeout: 4000,
    });

    return page.request.post("/os2forms-test-playwright/vcr/record", {
      data: {
        cassette,
      },
      timeout: 4000,
    });
  };

  const vcrEject = async (cassette: string) => {
    return page.request.post("/os2forms-test-playwright/vcr/eject", {
      timeout: 4000,
    });
  };

  await vcrRecord(context + "-add-handler");

  // Add handler
  await goto("admin/structure/webform/manage/os2forms_f2_test_000/handlers");
  await page
    .locator(
      '[data-drupal-link-system-path="admin/structure/webform/manage/os2forms_f2_test_000/handlers/add"]',
    )
    .click();
  await page.locator(".form-element--type-search").fill("F2");
  // Wait for the AJAX search
  await page.waitForTimeout(1000);
  await screenshot("webform-handler-select-f2", ".webform-ui-dialog");

  // Aarhus is very restrictive
  await drush([
    "--yes",
    "config:set",
    "os2forms_f2.settings",
    "general",
    "--input-format=yaml",
    JSON.stringify({
      matter_file_document_title_default_value: "[file:name]",
      archive_targets: {
        case: "case",
      },
      archive_target_case: {
        matter_type_default_value: "Internal",
        matter_type_allow_override: 0,
        matter_status_default_value: "Closed",
        matter_status_allow_override: 0,
        matter_access_level_default_value: "All",
        matter_access_level_allow_override: 0,
        matter_archived_default_value: 1,
        matter_archived_allow_override: 0,
      },
      queue: "os2forms_f2",
    }),
  ]);

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_000/handlers/add/os2forms_f2_f2",
  );
  await screenshot("webform-handler-add-general", "#webform-handler-form");
  await screenshot(
    "webform-handler-add-general-archive-settings-aarhus",
    "#edit-settings-os2forms-f2-f2",
  );

  // Allow overriding archive target and matter properties
  await drush([
    "--yes",
    "config:set",
    "os2forms_f2.settings",
    "general",
    "--input-format=yaml",
    JSON.stringify({
      matter_file_document_title_default_value: "[file:name]",
      archive_targets: {
        case: "case",
        matter: "matter",
      },
      archive_target_case: {
        matter_type_default_value: "Internal",
        matter_type_allow_override: 1,
        matter_status_default_value: "Closed",
        matter_status_allow_override: 1,
        matter_access_level_default_value: "All",
        matter_access_level_allow_override: 1,
        matter_archived_default_value: 1,
        matter_archived_allow_override: 1,
      },
      queue: "os2forms_f2",
    }),
  ]);

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_000/handlers/add/os2forms_f2_f2",
  );
  await screenshot("webform-handler-add-general", "#webform-handler-form");
  await screenshot(
    "webform-handler-add-general-archive-settings",
    "#edit-settings-os2forms-f2-f2",
  );

  // Archive on case
  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_000/handlers/add/os2forms_f2_f2",
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
    .locator('[name="settings[os2forms_f2_f2][archive][archive_files]"]')
    .check();
  // Wait for checkbox animation to (hopefully) complete
  await page.waitForTimeout(2000);
  await page
    .locator('[name="settings[os2forms_f2_f2][archive][archive_target]"]')
    .focus();

  await screenshot(
    "webform-handler-add-general-archive-settings-filled",
    "#edit-settings-os2forms-f2-f2",
  );

  await page
    .locator('[name="settings[os2forms_f2_f2][archive][archive_target]"]')
    .selectOption("case");
  await screenshot(
    "webform-handler-add-archive-on-case",
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
  await screenshot(
    "webform-handler-add-archive-on-case-filled",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-case",
  );

  await page.locator(submitSelector).click();
  await screenshot(
    "webform-handler-add-archive-on-case-filled-invalid-case-identifier",
    ".messages__wrapper",
  );

  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_case][case_identifier]"]',
    )
    .fill(process.env.F2_CASE_IDENTIFIER_VALID);
  await page.locator(submitSelector).click();
  await screenshot(
    "webform-handler-add-archive-on-case-filled-valid-case-identifier",
    ".messages__wrapper",
  );

  await goto("admin/structure/webform/manage/os2forms_f2_test_000/handlers");
  await screenshot("webform-handler-added", ".page-wrapper");

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_000/handlers/f2_arkiver_paa_sag/edit",
  );
  await screenshot(
    "webform-handler-archive-on-case-case-details",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-case",
  );

  // Archive on matter
  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_001/handlers/add/os2forms_f2_f2",
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

  // await screenshot('webform-handler-add-general-archive-settings-filled.png', '#edit-settings-os2forms-f2-f2');

  await page
    .locator('[name="settings[os2forms_f2_f2][archive][archive_target]"]')
    .selectOption("matter");
  await screenshot(
    "webform-handler-add-archive-on-matter",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-matter",
  );

  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_matter][matter_identifier]"]',
    )
    .fill(process.env.F2_MATTER_IDENTIFIER_INVALID);
  await screenshot(
    "webform-handler-add-archive-on-matter-filled",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-matter",
  );

  await page.locator(submitSelector).click();
  await screenshot(
    "webform-handler-add-archive-on-matter-filled-invalid-matter-identifier",
    ".messages__wrapper",
  );

  await page
    .locator(
      '[name="settings[os2forms_f2_f2][archive][archive_target_matter][matter_identifier]"]',
    )
    .fill(process.env.F2_MATTER_IDENTIFIER_VALID);
  await page.locator(submitSelector).click();
  await screenshot(
    "webform-handler-add-archive-on-matter-filled-valid-matter-identifier",
    ".messages__wrapper",
  );

  await goto("admin/structure/webform/manage/os2forms_f2_test_001/handlers");
  await screenshot("webform-handler-added-2", ".page-wrapper");

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_001/handlers/f2_arkiver_paa_akt/edit",
  );
  await screenshot(
    "webform-handler-archive-on-matter-matter-details",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-matter",
  );

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_000/handlers/f2_arkiver_paa_sag/edit",
  );
  await screenshot(
    "webform-handler-archive-on-case",
    "#edit-settings-os2forms-f2-f2",
  );
  await screenshot(
    "webform-handler-archive-on-case-case",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-case",
  );

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_001/handlers/f2_arkiver_paa_akt/edit",
  );
  await screenshot(
    "webform-handler-archive-on-matter",
    "#edit-settings-os2forms-f2-f2",
  );
  await screenshot(
    "webform-handler-archive-on-matter-matter",
    "#edit-settings-os2forms-f2-f2-archive-archive-target-matter",
  );

  await goto("admin/structure/webform/manage/os2forms_f2_test_000/handlers");
  await screenshot("webform-handlers", "#webform-handlers");

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_000/handlers/f2_arkiver_paa_sag/edit",
  );
  await page.getByRole("tab", { name: "Avanceret" }).click();
  await expect(page.getByText("Yderligere indstillinger")).toBeVisible();
  await page.getByText("Yderligere indstillinger").click();
  await page.getByText("Yderligere indstillinger").blur();
  await screenshot("webform-handler-add-advanced", "#webform-handler-form");

  await vcrEject();
});

test("webform submission", async ({ page }) => {
  const context = "webform-submission";
  const locale = "da";
  let screenshotCounter = 0;

  // @todo Do something about this code duplication!

  const goto = async (url: string) => {
    url = `/${locale}/${url}`;
    console.debug(`
going to ${url} …`);
    return page.goto(url);
  };

  const screenshot = async (name: string, selector: string = "main") => {
    const filePath = path.join(
      `/docs/user-manual/${locale}/images/generated/`,
      context,
      // String(screenshotCounter++).padStart(3, "0") + "-" +
      name + ".png",
    );
    const pagePath = page.url().replace(process.env.BASEURL, "");
    console.debug(`
screenshooting ${selector}
            on ${pagePath}
          into ${filePath}`);
    return page.locator(selector).screenshot({ path: filePath });
  };

  const drush = async (args: string[]) => {
    return page.request.post("/os2forms-test-playwright/exec/drush", {
      data: args,
      timeout: 4000,
    });
  };

  const vcrRecord = async (cassette: string) => {
    await page.request.post("/os2forms-test-playwright/vcr/configure", {
      data: {
        cassette_path: "../tests/vcr-cassettes/",
      },
      timeout: 4000,
    });

    return page.request.post("/os2forms-test-playwright/vcr/record", {
      data: {
        cassette,
      },
      timeout: 4000,
    });
  };

  const vcrEject = async (cassette: string) => {
    return page.request.post("/os2forms-test-playwright/vcr/eject", {
      timeout: 4000,
    });
  };

  await drush([
    "sql:query",
    `
TRUNCATE advancedqueue;
TRUNCATE webform_submission_log;
TRUNCATE webform_submission_data;
TRUNCATE webform_submission;
TRUNCATE os2forms_f2_f2_item;
ALTER TABLE webform_submission AUTO_INCREMENT = 1;
UPDATE webform SET next_serial = 1;
`,
  ]);

  await goto("webform/os2forms_f2_test_000");
  await screenshot("form-empty");

  await page
    .locator('input[data-drupal-selector="edit-name"]')
    .fill("Anders And");
  await screenshot("form-filled");

  await page.locator('input[data-drupal-selector="edit-submit"]').click();
  await screenshot("form-submitted");

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_000/submission/1/log",
  );
  await screenshot("submission-log");

  await vcrRecord(context);

  await drush(["advancedqueue:queue:process", "os2forms_f2"]);

  await goto(
    "admin/structure/webform/manage/os2forms_f2_test_000/submission/1/log",
  );
  await screenshot("submission-log-after-queue-processed");

  await vcrEject();
});
