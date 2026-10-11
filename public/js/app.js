"use strict";
document.querySelectorAll("[data-sales-chart]").forEach((card) => {
  const tabs = Array.from(card.querySelectorAll("[data-sales-tab]"));
  const selectPeriod = (tab) => {
    tabs.forEach((item) => {
      const selected = item === tab;
      item.classList.toggle("selected", selected);
      item.setAttribute("aria-selected", String(selected));
      item.tabIndex = selected ? 0 : -1;
    });
    card.querySelectorAll("[data-sales-panel]").forEach((panel) => {
      panel.hidden = panel.dataset.salesPanel !== tab.dataset.salesTab;
    });
    card.querySelector("[data-sales-period-field]").value =
      tab.dataset.salesTab;
    window.history.replaceState(null, "", tab.href);
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener("click", (event) => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey)
        return;
      event.preventDefault();
      selectPeriod(tab);
    });
    tab.addEventListener("keydown", (event) => {
      let next;
      if (event.key === "ArrowRight") next = (index + 1) % tabs.length;
      if (event.key === "ArrowLeft")
        next = (index + tabs.length - 1) % tabs.length;
      if (event.key === "Home") next = 0;
      if (event.key === "End") next = tabs.length - 1;
      if (next === undefined) return;
      event.preventDefault();
      selectPeriod(tabs[next]);
      tabs[next].focus();
    });
  });
  const money = new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    minimumFractionDigits: 2,
  });
  card.querySelectorAll("[data-sales-plot]").forEach((plot) => {
    const points = JSON.parse(plot.dataset.points);
    const dots = Array.from(plot.querySelectorAll("[data-sales-point]"));
    const tooltip = plot.querySelector("[data-sales-tooltip]");
    const scroller = plot.querySelector("[data-sales-scroll]");
    const bars = plot.querySelector("[data-sales-bars]");
    let selected = 0;
    const show = (index, announce = false) => {
      selected = Math.max(0, Math.min(points.length - 1, index));
      const point = points[selected];
      dots.forEach((dot, i) =>
        dot.classList.toggle("is-active", i === selected),
      );
      const bar = dots[selected];
      if (announce) {
        const barRect = bar.getBoundingClientRect();
        const scrollRect = scroller.getBoundingClientRect();
        if (barRect.left < scrollRect.left)
          scroller.scrollLeft += barRect.left - scrollRect.left;
        else if (barRect.right > scrollRect.right)
          scroller.scrollLeft += barRect.right - scrollRect.right;
      }
      tooltip.querySelector("[data-sales-tooltip-label]").textContent =
        point.description;
      tooltip.querySelector("[data-sales-tooltip-amount]").textContent =
        money.format(point.amount);
      tooltip.querySelector("[data-sales-tooltip-count]").textContent =
        `${point.payments} ${point.payments === 1 ? "payment" : "payments"}`;
      const breakdown = tooltip.querySelector("[data-sales-tooltip-methods]");
      breakdown.replaceChildren();
      Object.entries(point.methods).forEach(([method, amount]) => {
        if (Number(amount) <= 0) return;
        const row = document.createElement("span");
        row.textContent = `${method}: ${money.format(amount)}`;
        breakdown.append(row);
      });
      tooltip.hidden = false;
      const rect = bar.getBoundingClientRect();
      const left =
        rect.left -
        plot.getBoundingClientRect().left +
        rect.width / 2 -
        tooltip.offsetWidth / 2;
      tooltip.style.left = `${Math.max(0, Math.min(plot.clientWidth - tooltip.offsetWidth, left))}px`;
      if (announce)
        plot.querySelector("[data-sales-announcement]").textContent =
          `${point.description}: ${money.format(point.amount)}, ${point.payments} payments.`;
    };
    const hide = () => {
      tooltip.hidden = true;
      dots.forEach((dot) => dot.classList.remove("is-active"));
    };
    const explore = (event) => {
      const rect = bars.getBoundingClientRect();
      const fraction = (event.clientX - rect.left) / rect.width;
      show(Math.floor(fraction * points.length), event.type === "pointerdown");
    };
    plot.addEventListener("pointermove", explore);
    plot.addEventListener("pointerdown", explore);
    scroller.addEventListener("scroll", () => {
      if (document.activeElement === plot && !tooltip.hidden) show(selected);
      else hide();
    });
    plot.addEventListener("pointercancel", hide);
    plot.addEventListener("pointerleave", () => {
      if (document.activeElement !== plot) hide();
    });
    plot.addEventListener("focus", () => show(selected, true));
    plot.addEventListener("blur", hide);
    plot.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        hide();
        return;
      }
      let next;
      if (event.key === "ArrowRight") next = selected + 1;
      if (event.key === "ArrowLeft") next = selected - 1;
      if (event.key === "Home") next = 0;
      if (event.key === "End") next = points.length - 1;
      if (next === undefined) return;
      event.preventDefault();
      show(next, true);
    });
  });
});
document.querySelectorAll("[data-password]").forEach((button) =>
  button.addEventListener("click", () => {
    const input = document.getElementById(button.dataset.password);
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    button.setAttribute("aria-pressed", String(show));
    button.setAttribute("aria-label", `${show ? "Hide" : "Show"} password`);
  }),
);
document.querySelectorAll("[data-strength]").forEach((input) =>
  input.addEventListener("input", () => {
    const score = [
      input.value.length >= 8,
      /[A-Z]/.test(input.value),
      /[0-9]/.test(input.value),
      /[^A-Za-z0-9\s]/.test(input.value),
    ].filter(Boolean).length;
    document.getElementById("password-strength").value = score;
    document.getElementById("strength-label").textContent = [
      "Choose a strong password",
      "Needs more variety",
      "Getting stronger",
      "Almost there",
      "Meets password requirements",
    ][score];
  }),
);
document.querySelector("[data-menu]")?.addEventListener("click", (event) => {
  const open = document.getElementById("sidebar").classList.toggle("open");
  event.currentTarget.setAttribute("aria-expanded", String(open));
});
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    document.getElementById("sidebar")?.classList.remove("open");
    document
      .querySelector("[data-menu]")
      ?.setAttribute("aria-expanded", "false");
  }
});
document.addEventListener("click", (event) => {
  const sidebar = document.getElementById("sidebar");
  if (
    sidebar?.classList.contains("open") &&
    !sidebar.contains(event.target) &&
    !event.target.closest("[data-menu]")
  ) {
    sidebar.classList.remove("open");
    document
      .querySelector("[data-menu]")
      ?.setAttribute("aria-expanded", "false");
  }
});
document.querySelectorAll("form").forEach((form) =>
  form.addEventListener("submit", (event) => {
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
      event.preventDefault();
      return;
    }
    if (form.dataset.submitting) {
      event.preventDefault();
      return;
    }
    form.dataset.submitting = "true";
    form.setAttribute("aria-busy", "true");
    const button = event.submitter;
    if (button) {
      button.classList.add("busy");
      button.setAttribute("aria-disabled", "true");
    }
    // Keep submitter enabled so its name/value (repair consent) is included.
  }),
);
window.addEventListener("pageshow", () =>
  document.querySelectorAll("form").forEach((form) => {
    delete form.dataset.submitting;
    form.removeAttribute("aria-busy");
    form.querySelectorAll(".busy").forEach((b) => {
      b.classList.remove("busy");
      b.removeAttribute("aria-disabled");
    });
  }),
);
document
  .querySelectorAll("[data-print]")
  .forEach((button) => button.addEventListener("click", () => window.print()));
const lines = document.getElementById("order-lines");
document.querySelector("[data-add-line]")?.addEventListener("click", () => {
  if (lines.children.length >= 50) return;
  const clone = lines.firstElementChild.cloneNode(true);
  const index =
    Math.max(
      ...Array.from(lines.querySelectorAll("select")).map((s) =>
        Number(s.name.match(/\[(\d+)\]/)[1]),
      ),
    ) + 1;
  clone.querySelectorAll("input,select").forEach((input) => {
    const oldId = input.id;
    input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
    input.id = oldId.replace(/\d+$/, String(index));
    clone.querySelector(`label[for="${oldId}"]`).htmlFor = input.id;
    input.value = input.name.includes("[quantity]") ? "1" : "";
  });
  lines.appendChild(clone);
  clone.querySelector("select").focus();
});
lines?.addEventListener("click", (event) => {
  const button = event.target.closest("[data-remove-line]");
  if (
    button &&
    lines.children.length > 1 &&
    window.confirm("Remove this part from the draft?")
  )
    button.closest(".order-line").remove();
});
lines?.addEventListener("change", (event) => {
  if (event.target.matches("[data-order-part]"))
    event.target
      .closest(".order-line")
      .querySelector("[data-order-cost]").value =
      event.target.selectedOptions[0].dataset.cost ?? "";
});
