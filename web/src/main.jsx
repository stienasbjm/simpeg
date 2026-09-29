import React from "react";
import faviconUrl from "../../public/images/favicon.png";
import { createRoot } from "react-dom/client";
import "../../public/css/style.css";
import "./shell.css";
import "./features/print.css";
import App from "./App.jsx";
document.querySelector('link[rel="icon"]').href = faviconUrl;

createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
