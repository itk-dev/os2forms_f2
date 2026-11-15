import { APIResponse, Page } from "@playwright/test";
import path from "path";

export class DrupalPage {
  private page: Page;
  private locale: string;
  private context: string;
  private drushUrl: string;

  constructor(
    page: Page,
    {
      context = "drupal",
      locale = "da",
      drushUrl = "/os2forms-test-playwright/exec/drush",
    }: { context?: string; locale?: string; drushUrl?: string } = {},
  ) {
    this.page = page;
    this.context = context;
    this.locale = locale;
    this.drushUrl = drushUrl;
  }

  async goToPath(path: string, options = {}) {
    path = path.replace(/^\/+/, "");
    const url = `/${this.locale}/${path}`;
    console.debug(`
going to ${url} …`);
    return this.page.goto(url, options);
  }

  async takeScreenshot(
    name: string,
    {
      selector = "body",
    }: {
      selector?: string;
    } = {},
  ): Promise<any> {
    const filePath = path.join(
      `/docs/user-manual/${this.locale}/images/generated/`,
      this.context,
      name + ".png",
    );
    const pagePath = this.page.url().replace(process.env.BASEURL ?? "", "");
    console.debug(`
screenshooting ${selector}
            on ${pagePath}
          into ${filePath}`);
    // https://playwright.dev/docs/api/class-page#page-screenshot
    return this.page.locator(selector).screenshot({
      path: filePath,
      animations: "disabled",
      style: `
header.region-sticky--is-sticky,
header.region-sticky--is-sticky + .sticky-shadow {
  display: none;
}
`,
    });
  }

  async drush(args: string[]): Promise<APIResponse<any>> {
    return this.page.request.post(this.drushUrl, {
      data: args,
      timeout: 4000,
    });
  }
}
