const { chromium } = require("playwright");
const { AxeBuilder } = require("@axe-core/playwright");
const fs = require("fs");
const path = require("path");
const assert = require("node:assert/strict");
const base = process.env.TEST_URL || "http://localhost:8080";
const output = path.resolve(__dirname, "../test-results");
fs.mkdirSync(output, { recursive: true });
const config = Object.fromEntries(
  fs
    .readFileSync(path.resolve(__dirname, "../.env"), "utf8")
    .split("\n")
    .filter((l) => l.includes("=") && !l.startsWith("#"))
    .map((l) => [l.slice(0, l.indexOf("=")), l.slice(l.indexOf("=") + 1)]),
);
(async () => {
  const browser = await chromium.launch({
    headless: true,
    ...(process.env.CHROMIUM_PATH
      ? { executablePath: process.env.CHROMIUM_PATH, args: ["--no-sandbox"] }
      : {}),
  });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1000 },
    locale: "ru-RU",
  });
  const page = await context.newPage();
  const errors = [];
  const requests = [];
  const report = { pages: [], viewports: [], checks: [], errors };
  page.on("pageerror", (e) => errors.push(e.message));
  page.on("response", (r) => {
    if (r.status() >= 400 && r.url().startsWith(base))
      requests.push({ url: r.url(), status: r.status() });
  });
  try {
    for (const route of [
      "/",
      "/uslugi/",
      "/proekty/",
      "/o-studii/",
      "/kontakty/",
      "/privacy/",
      "/proekt/atelier/",
      "/usluga/corporate/",
    ]) {
      const response = await page.goto(base + route, {
        waitUntil: "networkidle",
      });
      assert.equal(response.status(), 200, route);
      assert.equal(await page.locator("html").getAttribute("lang"), "ru-RU");
      assert.equal(await page.locator("h1").count(), 1, route + " h1");
      // Проверяем устойчивое состояние: появление секций меняет opacity при прокрутке axe.
      await page.emulateMedia({ reducedMotion: "reduce" });
      // Переключение media ещё не гарантирует кадр с завершённым opacity-переходом.
      await page.evaluate(async () => {
        await document.fonts.ready;
        await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
      });
      await page.waitForFunction(() =>
        [...document.querySelectorAll("[data-horizon-reveal]")].every(element =>
          Number(getComputedStyle(element).opacity) === 1
        )
      );
      const result = await new AxeBuilder({ page })
        .withTags(["wcag2a", "wcag2aa", "wcag21aa"])
        .analyze();
      await page.emulateMedia({ reducedMotion: "no-preference" });
      report.pages.push({
        route,
        status: response.status(),
        a11y: result.violations.map((v) => ({
          id: v.id,
          impact: v.impact,
          nodes: v.nodes.map((n) => ({
            target: n.target,
            summary: n.failureSummary,
          })),
        })),
      });
      if (route === "/") {
        await page
          .locator("img")
          .evaluateAll((es) => es.forEach((e) => (e.loading = "eager")));
        await page.waitForFunction(() =>
          Array.from(document.images).every((i) => i.complete),
        );
        await page.screenshot({
          path: output + "/главная-десктоп.png",
          fullPage: true,
        });
        await page.screenshot({ path: output + "/первый-экран.png" });
      }
    }
    for (const width of [360, 390, 768, 1024, 1440, 1920]) {
      await page.setViewportSize({ width, height: 900 });
      await page.goto(base + "/", { waitUntil: "networkidle" });
      assert.equal(
        await page.evaluate(
          () => document.documentElement.scrollWidth > innerWidth,
        ),
        false,
        "Горизонтальная прокрутка " + width,
      );
      report.viewports.push({ width, overflow: false });
      if (width === 390) {
        await page
          .locator("img")
          .evaluateAll((es) => es.forEach((e) => (e.loading = "eager")));
        await page.waitForFunction(() =>
          Array.from(document.images).every((i) => i.complete),
        );
        await page.screenshot({
          path: output + "/главная-мобильная.png",
          fullPage: true,
        });
        const open = page
          .locator(".wp-block-navigation__responsive-container-open")
          .first();
        await open.click();
        await page
          .locator(".wp-block-navigation__responsive-container.is-menu-open")
          .waitFor();
        await page.keyboard.press("Escape");
        await page.waitForTimeout(550);
        assert.equal(
          await page
            .locator(".wp-block-navigation__responsive-container.is-menu-open")
            .count(),
          0,
        );
        report.checks.push(
          "Мобильное меню открывается и закрывается клавишей Escape",
        );
      }
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.goto(base + "/proekty/");
    await page.getByRole("link", { name: "Лендинги", exact: true }).click();
    assert.equal(await page.locator(".g-card").count(), 1);
    assert.match(await page.locator(".g-card").innerText(), /Фитнес/);
    report.checks.push("Фильтр портфолио");
    await page.goto(base + "/missing-qa-page/");
    assert.match(await page.locator("h1").innerText(), /за горизонтом/);
    report.checks.push("Русская страница 404");
    await page.goto(base + "/kontakty/");
    await page.locator('[name="name"]').fill("Тестовый посетитель");
    await page.locator('[name="email"]').fill("test@example.com");
    await page.locator('[name="message"]').fill("Нужен сайт для презентации компании.");
    const downloadPromise = page.waitForEvent("download");
    await page.getByRole("button", {name:"Скачать бриф проекта"}).click();
    const download = await downloadPromise;
    assert.equal(download.suggestedFilename(), "Бриф-проекта.txt");
    assert.match(await page.locator('[role="status"]').innerText(), /никуда не отправлялись/);
    report.checks.push("Бриф скачивается в файл без отправки персональных данных");
    const reduced = await browser.newContext({reducedMotion:"reduce"});
    const rp = await reduced.newPage(); await rp.goto(base + "/");
    assert.equal(await rp.locator(".horizon-intro").isVisible(), false);
    await reduced.close();
    report.checks.push("Настройка уменьшения анимации учитывается");
    await page.goto(base + "/wp-login.php");
    await page.locator("#user_login").fill(config.WP_ADMIN_USER);
    await page.locator("#user_pass").fill(config.WP_ADMIN_PASSWORD);
    await page.locator("#wp-submit").click();
    await page.waitForURL("**/wp-admin/**");
    await page.goto(base + "/wp-admin/admin.php?page=gorizont");
    await page
      .getByRole("heading", { name: "ГОРИЗОНТ — управление сайтом" })
      .waitFor();
    await page.screenshot({
      path: output + "/панель-управления.png",
      fullPage: true,
    });
    report.checks.push("Вход в русскую панель и настройки контактов");
    await page.goto(base + "/wp-admin/edit.php?post_type=g_service");
    await page
      .getByRole("link", { name: "Корпоративные сайты", exact: true })
      .first()
      .click();
    await page.waitForFunction(
      () =>
        window.wp &&
        wp.data &&
        wp.data.select("core/editor")?.getCurrentPostId(),
      null,
      { timeout: 60000 },
    );
    const validation = await page.evaluate(() => {
      const out = [];
      const walk = (bs) =>
        bs.forEach((b) => {
          if (b.isValid === false)
            out.push({ name: b.name, errors: b.validationIssues });
          walk(b.innerBlocks || []);
        });
      walk(wp.data.select("core/block-editor").getBlocks());
      return out;
    });
    assert.deepEqual(validation, [], "Валидность Gutenberg");
    const editing = await page.evaluate(async () => {
      const e = wp.data.select("core/editor");
      const id = e.getCurrentPostId();
      const original = e.getEditedPostAttribute("excerpt");
      wp.data
        .dispatch("core/editor")
        .editPost({ excerpt: original + " Проверка редактирования." });
      await wp.data.dispatch("core/editor").savePost();
      const persisted = await wp.apiFetch({
        path: "/wp/v2/g_service/" + id + "?context=edit",
      });
      wp.data.dispatch("core/editor").editPost({ excerpt: original });
      await wp.data.dispatch("core/editor").savePost();
      return persisted.excerpt.raw.includes("Проверка редактирования.");
    });
    assert.equal(editing, true);
    report.checks.push(
      "Изменение отрывка через Gutenberg сохраняется в БД; исходный текст восстановлен",
    );
    const homeData = await (
      await page.request.get(base + "/wp-json/wp/v2/pages?slug=glavnaya")
    ).json();
    const homeId = homeData[0].id;
    await page.goto(
      base + "/wp-admin/post.php?post=" + homeId + "&action=edit",
    );
    await page.waitForFunction(
      (homeId) =>
        window.wp &&
        wp.data &&
        wp.data.select("core/editor")?.getCurrentPostId() === homeId,
      homeId,
      { timeout: 60000 },
    );
    const invalid = await page.evaluate(() => {
      const names = [];
      const walk = (bs) =>
        bs.forEach((b) => {
          if (b.isValid === false)
            names.push({ name: b.name, issues: b.validationIssues });
          walk(b.innerBlocks || []);
        });
      walk(wp.data.select("core/block-editor").getBlocks());
      return names;
    });
    report.gutenbergInvalid = invalid;
    await page.screenshot({
      path: output + "/редактор-страницы.png",
      fullPage: true,
    });
    report.frontendFailedRequests = requests.filter(
      (r) => !r.url.includes("missing-qa-page"),
    );
    fs.writeFileSync(
      output + "/browser-report.json",
      JSON.stringify(report, null, 2),
    );
    console.log(
      JSON.stringify(
        {
          pages: report.pages.map((p) => ({
            route: p.route,
            violations: p.a11y.length,
            details: p.a11y,
          })),
          viewports: report.viewports,
          checks: report.checks,
          invalid,
          errors,
          failed: report.frontendFailedRequests,
        },
        null,
        2,
      ),
    );
    assert.deepEqual(invalid, [], "Главная содержит только валидные блоки Gutenberg");
    assert.deepEqual(errors, [], "Ошибки JavaScript");
    assert.deepEqual(report.frontendFailedRequests, [], "Не загруженные ресурсы");
    assert.equal(report.pages.reduce((count, p) => count + p.a11y.length, 0), 0, "Доступность WCAG AA");
  } finally {
    await browser.close();
  }
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
