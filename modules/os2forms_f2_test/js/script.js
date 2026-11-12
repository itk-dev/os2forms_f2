window.os2forms_f2_test = {
  revealMousePointer: (data) => {
    console.log("revealMousePointer", data);

    if (data.element) {
      const rect = data.element.getBoundingClientRect();
      data.x = rect.right - 10;
      data.y = rect.top + rect.height / 2;
    }

    const mousePointer = document.createElement("div");
    mousePointer.classList.add("os2forms-test", "mouse-pointer");
    mousePointer.style.left = data.x + "px";
    mousePointer.style.top = data.y + "px";
    document.body.appendChild(mousePointer);

    return ["revealMousePointer", data];
  },

  expandSelect: (data) => {
    console.log("expandSelect", data);

    const el = document.querySelector(data.selector);
    if (el) {
      const wrapper = document.createElement("div");
      el.after(wrapper);
      wrapper.classList.add("os2forms-test", "select-options-wrapper");
      wrapper.style.left = window.scrollX + el.offsetLeft + "px";
      wrapper.style.top = window.scrollX + el.offsetTop + "px";
      wrapper.style.width = el.offsetWidth + "px";
      const list = wrapper.appendChild(document.createElement("ol"));
      [...el.options].forEach((option, index) => {
        const item = list.appendChild(document.createElement("li"));
        item.innerText = option.text;
        item.classList.toggle("selected", option.selected);
        if (
          [option.text, option.value, index].indexOf(data.hover ?? null) > -1
        ) {
          item.classList.add("hover");
          if (data.mousePointer) {
            console.debug(
              os2forms_f2_test.revealMousePointer({
                element: item,
                pointer: data.mousePointer,
              }),
            );
          }
        }
      });
    }

    return ["expandSelect", data];
  },
};

// https://tom-select.js.org/examples/
addEventListener("load", () => {
  return;

  const buildOptionsDisplay = (el) => {
    let wrapper = el.os2forms_test_wrapper;
    if (!wrapper) {
      wrapper = document.createElement("div");
      wrapper.classList.add("os2forms-test", "select-options-wrapper");
      wrapper.style.left = el.offsetLeft + "px";
      wrapper.style.top = el.offsetTop + "px";
      wrapper.style.width = el.offsetWidth + "px";
      wrapper.appendChild(document.createElement("ol"));
      el.after(wrapper);
      el.os2forms_test_wrapper = wrapper;

      el.addEventListener("change", () => buildOptionsDisplay(event.target));
    }

    const list = wrapper.querySelector("ol");
    list.replaceChildren();
    [...el.options].forEach((option) => {
      const item = document.createElement("li");
      item.innerText = option.text;
      item.classList.toggle("selected", option.selected);
      list.appendChild(item);
    });
  };

  document.querySelectorAll("select").forEach(buildOptionsDisplay);

  return;
  const mousePointer = document.createElement("div");
  mousePointer.classList.add("os2forms-test", "mouse-pointer");
  document.body.appendChild(mousePointer);
  addEventListener("mousemove", (event) => {
    mousePointer.style.left = window.scrollX + event.clientX + "px";
    mousePointer.style.top = window.scrollY + event.clientY + "px";
    // console.debug(event)
  });
});
