import { cx } from "./primitives";

/** Nom du pays dans la langue de l'interface (ex. « BF » → « Burkina Faso »). */
export function countryName(iso2: string, locale: string): string {
  try {
    return new Intl.DisplayNames([locale], { type: "region" }).of(iso2.toUpperCase()) ?? iso2;
  } catch {
    return iso2;
  }
}

/** Drapeau SVG (public/flags, copié depuis country-flag-icons). Les emoji drapeaux ne s'affichent pas sous Windows. */
export function Flag({ iso2, size = 16, label, className }: { iso2?: string | null; size?: number; label?: string; className?: string }) {
  if (!iso2 || iso2.length !== 2) return null;
  const code = iso2.toUpperCase();
  return (
    <img
      src={`/flags/${code}.svg`}
      width={Math.round(size * 1.5)}
      height={size}
      alt={label ?? ""}
      aria-hidden={label ? undefined : true}
      title={label}
      loading="lazy"
      decoding="async"
      className={cx("inline-block shrink-0 rounded-[3px] object-cover align-[-0.15em] ring-1 ring-black/15", className)}
    />
  );
}
