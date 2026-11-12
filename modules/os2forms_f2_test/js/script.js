// https://tom-select.js.org/examples/
addEventListener("load", () => {
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

  const mousePointer = document.createElement("div");
  mousePointer.classList.add("os2forms-test", "mouse-pointer");
  document.body.appendChild(mousePointer);
  addEventListener("mousemove", (event) => {
    mousePointer.style.left = window.scrollX + event.clientX + "px";
    mousePointer.style.top = window.scrollY + event.clientY + "px";
    // console.debug(event)
  });
});
