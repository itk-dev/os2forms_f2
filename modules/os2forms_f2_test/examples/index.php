<html>
  <head>
    <style>
      body {
        background-color: white;
        padding: 10em;
      }
      section {
        width: 30%;
float: left;
      }
    </style>

    <link href="../css/module.css" rel="stylesheet"/>
  </head>
  <body>
    <section>
    <select>
      <option>A</option>
      <option selected>M a x</option>
      <option>– – –</option>
      <option>Aaa</option>
    </select>
    <script type="module">
      import { expandSelect } from "../js/module.js";
      const el = expandSelect({
        selector: "select",
        hover: "Aaa",
        mouse: {
          // cursor: "beachball",
          // position: ["left",],
        }
      })
      // console.debug({el})
      // setTimeout(() => {
      //   el.remove()
      // }, 2000);
    </script>
    </section>

    <section>
    <ol>
      <li>A</li>
      <li>B</li>
      <li>C</li>
    </ol>
    <script type="module">
      import { highlightElement } from "../js/module.js";
      console.debug(
        highlightElement({
          selector: "section + section > ol > li:nth-child(2)",
          color: 'orange',
          // offset: 5,
          width: 5,
        }),

        highlightElement({
          selector: "section + section > ol > li:nth-child(3)",
          type: 'rectangle',
          // offset: 5,
          width: 2,
          radius: 5,
        }),
      )
    </script>
    <script type="module">
      import { revealMousePointer } from "../js/module.js";
      revealMousePointer({
        selector: "section + section > ol > li:nth-child(1)",
        position: ['left'],
      })
    </script>
    </section>

    <section>
      <div style="width: 200px; height: 100px; border: solid 1px red; border-radius: 50%"></div>
    </section>

    <p>Donec hendrerit tempor tellus.</p>

    <a href="https://developer.apple.com/fonts/">https://developer.apple.com/fonts/</a>

  </body>
</html>
