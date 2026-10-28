/** @type {import('tailwindcss').Config} */
const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

export default {
  content: ["./index.html", "./src/**/*.{ts,tsx}"],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        brand: {
          red: "#FF2C19",
          "red-dark": "#E62816",
          gold: "#FFBB02",
          "gold-dark": "#E8AA02",
          green: "#25BF6A",
          "green-dark": "#20A65C",
        },
        bg: token("bg"),
        surface: token("surface"),
        ink: token("ink"),
        "ink-2": token("ink-2"),
        line: token("line"),
        success: { DEFAULT: token("success"), soft: token("success-soft") },
        danger: { DEFAULT: token("danger"), soft: token("danger-soft") },
        warning: { DEFAULT: token("warning"), soft: token("warning-soft") },
        info: { DEFAULT: token("info"), soft: token("info-soft") },
      },
      fontFamily: {
        title: ["Fredoka", "ui-rounded", "system-ui", "sans-serif"],
        body: ["Nunito", "system-ui", "sans-serif"],
      },
      boxShadow: {
        flat: "4px 4px 0 0 rgb(var(--shadow))",
        "flat-sm": "2px 2px 0 0 rgb(var(--shadow))",
      },
      borderRadius: {
        bubble: "1.25rem",
      },
      keyframes: {
        pop: { "0%": { transform: "scale(.96)", opacity: "0" }, "100%": { transform: "scale(1)", opacity: "1" } },
        rise: { "0%": { transform: "translateY(8px)", opacity: "0" }, "100%": { transform: "translateY(0)", opacity: "1" } },
      },
      animation: {
        pop: "pop 200ms ease-out",
        rise: "rise 250ms ease-out",
      },
    },
  },
  plugins: [],
};
