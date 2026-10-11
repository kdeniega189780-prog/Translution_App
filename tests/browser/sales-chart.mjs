import { chromium } from "playwright";
import { spawn, execFileSync } from "node:child_process";
import { mkdirSync, writeFileSync, existsSync } from "node:fs";
import path from "node:path";
import assert from "node:assert/strict";

const root = process.cwd();
const php = process.env.TEST_PHP || "C:/xampp/php/php.exe";
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
    await new Promise((resolve) => setTimeout(resolve, 200));
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
  page.on("pageerror", (error) => errors.push(error.message));
  await page.goto(env.APP_URL + "/login");
  await page.locator("#email").fill("admin@transolutions.test");
  await page.locator("#password").fill("BrowserFixture!42");
  await page.getByRole("button", { name: "Sign in", exact: true }).click();
  await page.waitForURL("**/profile");
  await page.locator("#current_password").fill("BrowserFixture!42");
  await page.locator("#password").fill("BrowserChanged!42");
  await page.locator("#password_confirmation").fill("BrowserChanged!42");
  await page.getByRole("button", { name: "Save profile" }).click();
  await page.waitForURL("**/dashboard");
  const chart = page.locator("[data-sales-chart]");
  assert.equal(
    await chart
      .getByRole("tab", { name: "Weekly" })
      .getAttribute("aria-selected"),
    "true",
  );
  for (const period of ["daily", "weekly", "monthly"]) {
    await chart.locator(`[data-sales-tab="${period}"]`).click();
    const panel = chart.locator(`[data-sales-panel="${period}"]`);
    assert.equal(await panel.isVisible(), true);
    assert.equal(
      await chart.locator("[data-sales-period-field]").inputValue(),
      period,
    );
    assert.match(
      await panel
        .getByRole("link", { name: "Full report" })
        .getAttribute("href"),
      new RegExp(`period=${period}`),
    );
    assert.match(
      await panel.locator(".sales-main-total").innerText(),
      /2,775\.00/,
    );
    const plot = panel.locator("[data-sales-plot]");
    const points = JSON.parse(await plot.getAttribute("data-points"));
    assert.equal(await plot.locator(".sales-bar-track").count(), points.length);
    const heights = await plot
      .locator(".sales-bar-fill")
      .evaluateAll((bars) =>
        bars.map((bar) => bar.getBoundingClientRect().height),
      );
    points.forEach((point, index) => {
      assert.equal(heights[index] > 0, Number(point.amount) > 0);
    });
    await plot.hover();
    assert.equal(await panel.locator("[data-sales-tooltip]").isVisible(), true);
    await plot.focus();
    await plot.press("End");
    assert.match(
      await plot.locator("[data-sales-announcement]").innerText(),
      /payments/,
    );
    await plot.press("Escape");
    assert.equal(
      await panel.locator("[data-sales-tooltip]").isVisible(),
      false,
    );
    await chart.screenshot({
      path: `test-results/sales-${period}-desktop.png`,
    });
  }
  await chart.getByRole("tab", { name: "Monthly" }).focus();
  await page.keyboard.press("ArrowLeft");
  assert.equal(
    await chart
      .getByRole("tab", { name: "Weekly" })
      .getAttribute("aria-selected"),
    "true",
  );
  for (const width of [390, 320]) {
    await page.setViewportSize({ width, height: 844 });
    for (const period of ["daily", "weekly", "monthly"]) {
      await chart.locator(`[data-sales-tab="${period}"]`).click();
      const mobilePlot = page.locator(
        `[data-sales-panel="${period}"] [data-sales-plot]`,
      );
      await mobilePlot.focus();
      await mobilePlot.press("End");
      const lastBar = mobilePlot.locator("[data-sales-point]").last();
      const barBounds = await lastBar.boundingBox();
      const scrollBounds = await mobilePlot
        .locator("[data-sales-scroll]")
        .boundingBox();
      assert.ok(barBounds.x >= scrollBounds.x - 1);
      assert.ok(
        barBounds.x + barBounds.width <=
          scrollBounds.x + scrollBounds.width + 1,
      );
      const mobilePoints = JSON.parse(
        await mobilePlot.getAttribute("data-points"),
      );
      assert.equal(
        await mobilePlot.locator("[data-sales-tooltip-label]").innerText(),
        mobilePoints.at(-1).description,
      );
      await mobilePlot.press("Home");
      await mobilePlot.press("Escape");
      assert.equal(
        await page.evaluate(
          () => document.documentElement.scrollWidth > innerWidth,
        ),
        false,
        `${period} overflow at ${width}px`,
      );
    }
    await chart.screenshot({ path: `test-results/sales-monthly-${width}.png` });
  }
  await page.locator("#sales_date").fill("2024-02-29");
  await chart.getByRole("button", { name: "View", exact: true }).click();
  await page.waitForURL("**/dashboard?**");
  const monthly = page.locator('[data-sales-panel="monthly"]');
  await monthly.waitFor({ state: "visible" });
  assert.equal(
    JSON.parse(
      await monthly.locator("[data-sales-plot]").getAttribute("data-points"),
    ).length,
    29,
  );
  assert.match(
    await monthly.locator(".sales-chart-footer").innerText(),
    /No payments recorded/,
  );
  assert.match(await monthly.locator(".sales-main-total").innerText(), /0\.00/);
  assert.equal(
    await monthly
      .locator(".sales-bar-fill")
      .evaluateAll((bars) =>
        bars.every((bar) => bar.getBoundingClientRect().height === 0),
      ),
    true,
  );
  execFileSync(php, ["artisan", "db:seed", "--class=WorkshopDemoSeeder"], {
    env,
    stdio: "pipe",
  });
  await page.goto(env.APP_URL + "/dashboard?sales_period=monthly");
  const samplePanel = page.locator('[data-sales-panel="monthly"]');
  await samplePanel.waitFor({ state: "visible" });
  const samplePoints = JSON.parse(
    await samplePanel.locator("[data-sales-plot]").getAttribute("data-points"),
  );
  assert.ok(samplePoints.some((point) => Number(point.amount) > 2775));
  const sampleHeights = await samplePanel
    .locator(".sales-bar-fill")
    .evaluateAll((bars) =>
      bars.map((bar) => bar.getBoundingClientRect().height),
    );
  const largest = Math.max(
    ...samplePoints.map((point) => Number(point.amount)),
  );
  const tallest = Math.max(...sampleHeights);
  samplePoints.forEach((point, index) => {
    assert.ok(
      Math.abs(
        sampleHeights[index] / tallest - Number(point.amount) / largest,
      ) < 0.01,
    );
  });
  const stacks = await samplePanel
    .locator(".sales-bar-fill")
    .evaluateAll((bars) =>
      bars.map((bar) =>
        Array.from(bar.children).map((segment) => ({
          method: segment.dataset.salesMethod,
          height: segment.getBoundingClientRect().height,
        })),
      ),
    );
  samplePoints.forEach((point, index) => {
    const expected = Object.entries(point.methods).filter(
      ([, amount]) => Number(amount) > 0,
    );
    assert.deepEqual(
      stacks[index].map((segment) => segment.method),
      expected.map(([method]) => method),
    );
    stacks[index].forEach((segment) => {
      assert.ok(
        Math.abs(
          segment.height / sampleHeights[index] -
            Number(point.methods[segment.method]) / Number(point.amount),
        ) < 0.01,
      );
    });
  });
  const paidIndex = samplePoints.findIndex((point) => Number(point.amount) > 0);
  await samplePanel.locator(`[data-sales-point="${paidIndex}"]`).click();
  const breakdown = await samplePanel
    .locator("[data-sales-tooltip-methods]")
    .innerText();
  Object.entries(samplePoints[paidIndex].methods).forEach(
    ([method, amount]) => {
      if (Number(amount) > 0) assert.ok(breakdown.includes(method));
    },
  );
  await samplePanel.locator("[data-sales-plot]").press("Escape");
  for (const width of [1440, 390, 320]) {
    await page.setViewportSize({ width, height: 1000 });
    assert.equal(
      await page.evaluate(
        () => document.documentElement.scrollWidth > innerWidth,
      ),
      false,
      `Sample sales chart overflow at ${width}px`,
    );
    await chart.screenshot({ path: `test-results/sales-demo-${width}.png` });
    await chart.getByRole("tab", { name: "Weekly" }).click();
    await chart.screenshot({
      path: `test-results/sales-layered-weekly-${width}.png`,
      animations: "disabled",
    });
    await chart.getByRole("tab", { name: "Monthly" }).click();
  }
  assert.deepEqual(errors, []);
  console.log(
    "Sales chart browser checks passed: three periods, totals, tooltip, keyboard navigation, date filter, empty/leap month, and desktop/390px/320px layouts.",
  );
} finally {
  await browser?.close();
  server.kill();
}
