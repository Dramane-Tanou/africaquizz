import React from "react";
import ReactDOM from "react-dom/client";
import { BrowserRouter } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
// Polices limitées à 3 fichiers woff2 (~50 Ko) pour les réseaux lents (§9.5).
import "@fontsource/fredoka/latin-600.css";
import "@fontsource/nunito/latin-400.css";
import "@fontsource/nunito/latin-700.css";
import "./index.css";
import "./i18n";
import { normalizeError } from "./lib/api";
import { SettingsProvider } from "./context/SettingsContext";
import { AuthProvider } from "./context/AuthContext";
import App from "./App";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Pas de nouvel essai pour les erreurs métier (4xx), seulement réseau / serveur.
      retry: (count, err) => {
        const k = normalizeError(err).kind;
        return (k === "network" || k === "server") && count < 2;
      },
      refetchOnWindowFocus: false,
    },
  },
});

ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <SettingsProvider>
          <AuthProvider>
            <App />
          </AuthProvider>
        </SettingsProvider>
      </BrowserRouter>
    </QueryClientProvider>
  </React.StrictMode>,
);
