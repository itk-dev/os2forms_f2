// https://tom-select.js.org/examples/
addEventListener("load", () => {
  const buildOptionsDisplay = (el) => {
    let wrapper = el.os2forms_test_wrapper;
    if (!wrapper) {
      wrapper = document.createElement("div");
      wrapper.classList.add("os2forms-test");
      wrapper.classList.add("select-options-wrapper");
      wrapper.style.top = el.offsetTop + "px";
      wrapper.style.left = el.offsetLeft + "px";
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
});
