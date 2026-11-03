// Copie les drapeaux SVG (country-flag-icons, licence MIT) dans public/flags/ avant dev et build.
// Les emoji drapeaux ne s'affichent pas sous Windows : on sert de vraies images.
import { cpSync, existsSync, mkdirSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const source = join(root, "node_modules", "country-flag-icons", "3x2");
const target = join(root, "public", "flags");

if (!existsSync(source)) {
  console.error("country-flag-icons introuvable : lance npm ci.");
  process.exit(1);
}
mkdirSync(target, { recursive: true });
cpSync(source, target, { recursive: true, filter: (path) => !path.endsWith(".js") });
