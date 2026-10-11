import { chromium } from "playwright";
import { spawn, execFileSync } from "node:child_process";
import { mkdirSync, writeFileSync, existsSync } from "node:fs";
import path from "node:path";
import assert from "node:assert/strict";
const root = process.cwd(),
  php = process.env.TEST_PHP || "C:/xampp/php/php.exe";
mkdirSync("test-results", { recursive: true });
const database = path.join(root, "test-results/browser.sqlite");
if (!existsSync(database)) writeFileSync(database, "");
const env = {
  ...process.env,
  APP_ENV: "testing",
  APP_DEBUG: "false",
  APP_URL: "http://127.0.0.1:8091",
  DB_CONNECTION: "sqlite",
  DB_DATABASE: database,
  SESSION_DRIVER: "file",
  CACHE_STORE: "file",
  MAIL_MAILER: "log",
  BCRYPT_ROUNDS: "4",
};
execFileSync(php, ["tests/browser/prepare.php"], { env, stdio: "inherit" });
const server = spawn(
  php,
  [
    "-S",
    "127.0.0.1:8091",
    "-t",
    path.join(root, "public"),
    path.join(
      root,
      "vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php",
    ),
  ],
  { env, cwd: path.join(root, "public"), stdio: "ignore", windowsHide: true },
);
let browser;
try {
  for (let n = 0; n < 50; n++) {
    try {
      if ((await fetch(env.APP_URL + "/login")).ok) break;
    } catch {}
    await new Promise((r) => setTimeout(r, 200));
  }
  browser = await chromium.launch({
    channel: process.env.TEST_BROWSER || "msedge",
    headless: true,
  });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1050 },
  });
  const page = await context.newPage();
  const errors = [];
  page.on("pageerror", (e) => errors.push(e.message));
  page.on("dialog", (dialog) => dialog.accept());
  async function recordStockOut(role) {
    await page.goto(env.APP_URL + "/inventory");
    await page
      .getByRole("link", { name: /^Stock Out:/ })
      .first()
      .click();
    const stockOutUrl = page.url();
    const available = Number.parseInt(
      await page.locator(".details dd").last().innerText(),
      10,
    );
    await page.getByLabel("Quantity to remove").fill(String(available + 1));
    assert.equal(
      await page
        .locator("#quantity")
        .evaluate((el) => el.validity.rangeOverflow),
      true,
    );
    await page.getByLabel("Quantity to remove").fill("1");
    const reason = `${role}: damaged item returned to supplier`;
    await page.getByLabel("Reason for stock out").fill(reason);
    const viewport = page.viewportSize();
    for (const width of [1440, 320]) {
      await page.setViewportSize({ width, height: 1000 });
      assert.equal(
        await page.evaluate(
          () => document.documentElement.scrollWidth > innerWidth,
        ),
        false,
        `Stock Out overflow at ${width}`,
      );
      await page.screenshot({
        path: `test-results/stock-out-${role}-${width}.png`,
        fullPage: true,
        animations: "disabled",
      });
    }
    await page.setViewportSize(viewport);
    await page
      .getByRole("button", { name: "Record stock out", exact: true })
      .click();
    await page.waitForURL("**/inventory");
    assert.equal(
      await page
        .getByText("Stock out recorded. Inventory quantity updated.", {
          exact: true,
        })
        .count(),
      1,
    );
    const movement = page
      .locator("#stock-movements tbody tr")
      .filter({ hasText: reason });
    assert.equal(await movement.locator("td").nth(2).innerText(), "Stock Out");
    assert.equal(await movement.locator("td").nth(3).innerText(), "-1");
    assert.equal(
      await movement.locator("td").nth(4).innerText(),
      String(available - 1),
    );
    await page.goto(stockOutUrl);
    assert.match(
      await page.locator(".details dd").last().innerText(),
      new RegExp(`^${available - 1} units$`),
    );
  }
  await page.goto(env.APP_URL + "/");
  assert.equal(
    await page.getByRole("heading", { name: /A smoother ride/ }).count(),
    1,
  );
  for (const width of [1440, 390, 320]) {
    await page.setViewportSize({ width, height: 1050 });
    assert.equal(
      await page.evaluate(
        () => document.documentElement.scrollWidth > innerWidth,
      ),
      false,
      "Landing overflow at " + width,
    );
    await page.screenshot({
      path: "test-results/landing-" + width + ".png",
      fullPage: true,
    });
  }
  await page
    .getByRole("link", { name: "Sign in", exact: true })
    .first()
    .click();
  await page.waitForURL("**/login");
  await page.setViewportSize({ width: 1440, height: 1050 });
  await page.screenshot({
    path: "test-results/login-desktop.png",
    fullPage: true,
  });
  await page.getByLabel("Email address").fill("admin@transolutions.test");
  await page.locator("#password").fill("BrowserFixture!42");
  await page.getByRole("button", { name: "Sign in", exact: true }).click();
  await page.waitForURL("**/profile");
  await page.locator("#current_password").fill("BrowserFixture!42");
  await page.locator("#password").fill("BrowserChanged!42");
  await page.locator("#password_confirmation").fill("BrowserChanged!42");
  await page.getByRole("button", { name: "Save profile" }).click();
  await page.waitForURL("**/dashboard");
  await page.screenshot({
    path: "test-results/dashboard-desktop.png",
    fullPage: true,
  });
  assert.equal(
    await page
      .getByRole("heading", { name: "Daily workshop schedule" })
      .count(),
    1,
  );
  for (const url of [
    "/bookings",
    "/customers",
    "/vehicles",
    "/services",
    "/inventory",
    "/orders",
    "/invoices",
    "/reports",
    "/users",
  ]) {
    const response = await page.goto(env.APP_URL + url);
    assert.equal(response.status(), 200, url);
    assert.equal(
      await page.evaluate(
        () => document.documentElement.scrollWidth > innerWidth,
      ),
      false,
      "Overflow: " + url,
    );
  }
  await page.goto(env.APP_URL + "/bookings/1");
  const nextDay = new Date();
  nextDay.setDate(nextDay.getDate() + 2);
  await page
    .locator("#scheduled_at")
    .fill(nextDay.toISOString().slice(0, 10) + "T09:00");
  await page.getByRole("button", { name: "Next step: Schedule" }).click();
  await page
    .locator("#assigned_staff_id")
    .selectOption({ label: "Marco Reyes" });
  await page.getByRole("button", { name: "Next step: Start job" }).click();
  await page
    .locator("#diagnosis")
    .fill("Replace the transmission filter and road test.");
  await page.getByRole("button", { name: "Save diagnosis" }).click();
  await page
    .locator("#consent_note")
    .fill("Customer approved by phone during the browser test.");
  await page.getByRole("button", { name: "Agree to repair" }).click();
  await page.locator("#part_id").selectOption("2");
  await page.locator("#quantity").fill("1");
  await page.getByRole("button", { name: "Record parts used" }).click();
  await page.getByRole("button", { name: "Next step: Complete" }).click();
  await page.getByRole("link", { name: "Next step: View invoice" }).click();
  await page.locator("#payment_method").selectOption("Cash");
  await page
    .getByRole("button", { name: "Mark paid & release vehicle" })
    .click();
  assert.equal(
    await page.getByText("Vehicle released.", { exact: false }).count(),
    1,
  );
  await page.screenshot({
    path: "test-results/invoice-desktop.png",
    fullPage: true,
  });
  await recordStockOut("admin");
  await page.goto(env.APP_URL + "/reports");
  const [download] = await Promise.all([
    page.waitForEvent("download"),
    page.getByRole("link", { name: "Export PDF" }).click(),
  ]);
  await download.saveAs("test-results/report.pdf");
  await page.setViewportSize({ width: 390, height: 844 });
  for (const url of [
    "/dashboard",
    "/bookings/1",
    "/orders/create",
    "/reports",
  ]) {
    await page.goto(env.APP_URL + url);
    await page.screenshot({
      path:
        "test-results/" +
        (url === "/dashboard" ? "dashboard-mobile" : url.replaceAll("/", "-")) +
        ".png",
      fullPage: true,
    });
    if (
      await page.evaluate(
        () => document.documentElement.scrollWidth > innerWidth,
      )
    )
      console.log(
        await page.evaluate(() =>
          Array.from(document.querySelectorAll("body *"))
            .filter((el) => el.getBoundingClientRect().right > innerWidth + 1)
            .map((el) => ({
              tag: el.tagName,
              cls: el.className,
              width: el.getBoundingClientRect().width,
              right: el.getBoundingClientRect().right,
            }))
            .slice(0, 20),
        ),
      );
    assert.equal(
      await page.evaluate(
        () => document.documentElement.scrollWidth > innerWidth,
      ),
      false,
      "Mobile overflow: " + url,
    );
  }
  await page.getByRole("button", { name: "Menu" }).click();
  await page.waitForFunction(
    () =>
      Math.abs(
        document.getElementById("sidebar").getBoundingClientRect().left,
      ) < 1,
  );
  assert.ok(
    Math.abs(
      await page
        .locator("#sidebar")
        .evaluate((el) => el.getBoundingClientRect().left),
    ) < 1,
  );
  const badCsrf = await context.request.post(
    env.APP_URL + "/notifications/read",
    { form: {} },
  );
  assert.equal(badCsrf.status(), 419, "CSRF missing token must be rejected");
  for (const role of ["secretary", "mechanic", "purchaser", "customer"]) {
    await context.clearCookies();
    await page.goto(env.APP_URL + "/login");
    await page.locator("#email").fill(role + "@transolutions.test");
    await page.locator("#password").fill("BrowserFixture!42");
    await page.getByRole("button", { name: "Sign in", exact: true }).click();
    await page.waitForURL("**/profile");
    await page.locator("#current_password").fill("BrowserFixture!42");
    await page.locator("#password").fill("BrowserChanged!42");
    await page.locator("#password_confirmation").fill("BrowserChanged!42");
    await page.getByRole("button", { name: "Save profile" }).click();
    await page.waitForURL("**/dashboard");
    if (role === "purchaser") {
      await recordStockOut(role);
      await page.goto(env.APP_URL + "/dashboard");
    }
    assert.equal(
      await page.evaluate(
        () => document.documentElement.scrollWidth > innerWidth,
      ),
      false,
      role + " mobile overflow",
    );
    if (["secretary", "mechanic"].includes(role)) {
      assert.equal(
        await page
          .getByRole("heading", {
            name:
              role === "mechanic"
                ? "On your repair bench"
                : "Booking & assignment queue",
          })
          .count(),
        1,
      );
      await page.setViewportSize({ width: 1440, height: 1050 });
      await page.screenshot({
        path: "test-results/" + role + "-desktop.png",
        fullPage: true,
      });
      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto(env.APP_URL + "/bookings/3");
      assert.equal(
        await page.getByRole("button", { name: "Record parts used" }).count(),
        role === "mechanic" ? 1 : 0,
      );
      assert.equal(
        await page.getByRole("button", { name: "Next step: Complete" }).count(),
        role === "mechanic" ? 1 : 0,
      );
      assert.equal(
        await page.evaluate(
          () => document.documentElement.scrollWidth > innerWidth,
        ),
        false,
        role + " job overflow",
      );
      await page.goto(env.APP_URL + "/dashboard");
    }
    await page.screenshot({
      path: `test-results/${role}-mobile.png`,
      fullPage: true,
    });
  }
  assert.deepEqual(errors, []);
  console.log(
    "Browser checks passed: all roles, first password change, full paid repair, admin/purchaser stock out, PDF, CSRF, desktop and mobile layout.",
  );
} finally {
  await browser?.close();
  server.kill();
}
