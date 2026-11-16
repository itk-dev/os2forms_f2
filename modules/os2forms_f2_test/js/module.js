const getElement = (selector) => {
  if (!selector) {
    throw new Error("Selector not defined");
  }

  const els = document.querySelectorAll(selector);
  if (0 === els.length) {
    throw new Error(`Selector '${selector}' matches no elements`);
  }
  if (els.length > 1) {
    throw new Error(`Selector '${selector}' matches multiple elements`);
  }

  return els[0];
};

const px = (value) => value.toFixed(4) + "px";

const createElement = (name, attributes, ...children) => {
  const element = document.createElement(name);
  Object.entries(attributes ?? {}).forEach((entry) => {
    const [name, value] = entry;
    if ("class" === name) {
      element.classList.add(...value);
    } else {
      element.setAttribute(name, value);
    }
    console.log(name, value);
  });
  document.body.appendChild(element);

  return element;
};

const revealMousePointer = ({ element, selector, x, y, cursor, position }) => {
  console.log("revealMousePointer", { element, x, y });
  if (!position) {
    position = [];
  }

  if (selector) {
    element = getElement(selector);
  }

  if (element) {
    const rect = element.getBoundingClientRect();
    const left = position[0] ?? "center";
    const top = position[1] ?? "center";

    let offsetX = rect.width / 2;
    if (Number.isInteger(left)) {
      offsetX = left > 0 ? left : rect.width + left;
    } else {
      switch (left) {
        case "left":
          offsetX = 0;
          break;
        case "right":
          offsetX = rect.width;
          break;
      }
    }

    let offsetY = rect.height / 2;
    if (Number.isInteger(top)) {
      offsetY = top > 0 ? top : rect.height + top;
    } else {
      switch (top) {
        case "top":
          offsetY = 0;
          break;
        case "bottom":
          offsetY = rect.height;
          break;
      }
    }

    x = rect.left + offsetX;
    y = rect.top + offsetY;
  }

  const mousePointer = createElement("div", { class: ["ui-mouse-pointer"] });
  if (cursor) {
    mousePointer.classList.add("cursor-" + cursor);
  }
  mousePointer.style.left = px(x);
  mousePointer.style.top = px(y);

  return mousePointer;
};

const expandSelect = ({ selector, hover, mouse }) => {
  console.log("expandSelect", { selector, hover, mouse });

  if (!selector) {
    throw new Error("Selector not defined");
  }

  const el = document.querySelector(selector);
  if (!el) {
    throw new Error(`Selector '${selector}' matches no elements`);
  }
  if (!el.matches("select")) {
    throw new Error(`Element ${el} is not a select element`);
  }
  if (el.multiple) {
    throw new Error(`Element ${el} is a multiple select`);
  }
  const rect = el.getBoundingClientRect();
  // const wrapper = document.createElement("div");
  // el.after(wrapper);
  // wrapper.classList.add("ui-select");
  const wrapper = createElement("div", { class: ["ui-select"] });
  wrapper.style.left = px(rect.left);
  wrapper.style.top = px(rect.top);
  wrapper.style.width = px(el.offsetWidth);
  [...el.options].forEach((option, index) => {
    const item = wrapper.appendChild(document.createElement("div"));
    item.innerText = option.text;
    item.classList.toggle("selected", option.selected);
    if ([option.text, option.value, index].indexOf(hover ?? null) > -1) {
      item.classList.add("hover");
      if (mouse) {
        console.debug(
          revealMousePointer({
            element: item,
            position: [-10, "center"],
            ...mouse,
          }),
        );
      }
    }
  });

  return wrapper;
};

/**
 *
 * @param param0
 * @param param0.selector
 * @param param0.type
 * @param param0.color
 * @param param0.width
 * @param param0.offset
 * @param param0.radius
 * @returns {HTMLAnchorElement | HTMLElement | HTMLAreaElement | HTMLAudioElement | HTMLBaseElement | HTMLQuoteElement | HTMLBodyElement | HTMLBRElement | HTMLButtonElement | HTMLCanvasElement | HTMLTableCaptionElement | HTMLTableColElement | HTMLDataElement | HTMLDataListElement | HTMLModElement | HTMLDetailsElement | HTMLDialogElement | HTMLDivElement | HTMLDListElement | HTMLEmbedElement | HTMLFieldSetElement | HTMLFormElement | HTMLHeadingElement | HTMLHeadElement | HTMLHRElement | HTMLHtmlElement | HTMLIFrameElement | HTMLImageElement | HTMLInputElement | HTMLLabelElement | HTMLLegendElement | HTMLLIElement | HTMLLinkElement | HTMLMapElement | HTMLMenuElement | HTMLMetaElement | HTMLMeterElement | HTMLObjectElement | HTMLOListElement | HTMLOptGroupElement | HTMLOptionElement | HTMLOutputElement | HTMLParagraphElement | HTMLPictureElement | HTMLPreElement | HTMLProgressElement | HTMLScriptElement | HTMLSelectElement | HTMLSlotElement | HTMLSourceElement | HTMLSpanElement | HTMLStyleElement | HTMLTableElement | HTMLTableSectionElement | HTMLTableCellElement | HTMLTemplateElement | HTMLTextAreaElement | HTMLTimeElement | HTMLTitleElement | HTMLTableRowElement | HTMLTrackElement | HTMLUListElement | HTMLVideoElement}
 */
const highlightElement = ({
  selector,
  type = "ellipse",
  color = "green",
  width = 1,
  offset = 0,
  radius = 0,
}) => {
  console.debug("highlightElement", selector, type);

  const el = getElement(selector);
  const rect = el.getBoundingClientRect();
  const wrapper = createElement("div", { class: ["ui-highlight", type] });

  wrapper.style.borderColor = color;
  wrapper.style.borderWidth = px(width);

  if ("ellipse" === type) {
    // https://stackoverflow.com/a/433426
    const center = new DOMPoint(
      rect.x + rect.width / 2,
      rect.y + rect.height / 2,
    );
    const highlightWidth = Math.SQRT2 * rect.width;
    const highlightHeight = Math.SQRT2 * rect.height;

    wrapper.style.left = px(center.x - highlightWidth / 2 - offset - width / 2);
    wrapper.style.top = px(center.y - highlightHeight / 2 - offset - width / 2);
    wrapper.style.width = px(highlightWidth + 2 * offset + width / 2);
    wrapper.style.height = px(highlightHeight + 2 * offset + width / 2);
    // } else if ("filled-rectangle") {
    // } else if ("blur") {
  } else {
    // rectangle
    wrapper.style.left = px(rect.left - offset - width / 2);
    wrapper.style.top = px(rect.top - offset - width / 2);
    wrapper.style.width = px(rect.width + 2 * offset + width / 2);
    wrapper.style.height = px(rect.height + 2 * offset + width / 2);
    wrapper.style.borderRadius = px(radius);
  }

  return wrapper;
};

export { revealMousePointer, expandSelect, highlightElement };
