import { cx } from "./primitives";

/** Marque provisoire : bulle de dialogue aux trois couleurs (en attendant le logo vectoriel du graphiste). */
export function LogoMark({ size = 40, className }: { size?: number; className?: string }) {
  return (
    <svg width={size} height={size} viewBox="0 0 64 64" className={className} aria-hidden>
      <path d="M8 10h48a6 6 0 0 1 6 6v26a6 6 0 0 1-6 6H28l-12 10v-10H8a6 6 0 0 1-6-6V16a6 6 0 0 1 6-6z" fill="#000" transform="translate(2 2)" />
      <path d="M8 10h48a6 6 0 0 1 6 6v26a6 6 0 0 1-6 6H28l-12 10v-10H8a6 6 0 0 1-6-6V16a6 6 0 0 1 6-6z" fill="#FFBB02" stroke="#000" strokeWidth="3" />
      <circle cx="20" cy="29" r="5" fill="#FF2C19" stroke="#000" strokeWidth="2.5" />
      <circle cx="32" cy="29" r="5" fill="#FFBB02" stroke="#000" strokeWidth="2.5" />
      <circle cx="44" cy="29" r="5" fill="#25BF6A" stroke="#000" strokeWidth="2.5" />
    </svg>
  );
}

export function Logo({ className, size = 36 }: { className?: string; size?: number }) {
  return (
    <span className={cx("inline-flex items-center gap-2", className)}>
      <LogoMark size={size} />
      <span className="font-title text-2xl font-bold leading-none tracking-tight text-ink">
        Africa <span className="text-brand-red">Q</span>
        <span className="text-brand-gold-dark">ui</span>
        <span className="text-brand-green-dark">zz</span>
      </span>
    </span>
  );
}

// Avatars prédéfinis (motifs géométriques inspirés des tissus, sans visage ni cliché).
const AVATARS = [
  { bg: "#FFBB02", fg: "#000", shape: "diamond" },
  { bg: "#25BF6A", fg: "#000", shape: "zigzag" },
  { bg: "#FF2C19", fg: "#000", shape: "circles" },
  { bg: "#1D4ED8", fg: "#FFBB02", shape: "triangles" },
  { bg: "#8A5A00", fg: "#FFF4D6", shape: "stripes" },
  { bg: "#0E7A4B", fg: "#FFBB02", shape: "diamond" },
  { bg: "#111111", fg: "#25BF6A", shape: "zigzag" },
  { bg: "#C62828", fg: "#FFF9EB", shape: "triangles" },
] as const;

export const AVATAR_COUNT = AVATARS.length;

export function Avatar({ index = 0, size = 56, label, initial: name, className }: { index?: number; size?: number; label?: string; initial?: string; className?: string }) {
  const a = AVATARS[((index % AVATARS.length) + AVATARS.length) % AVATARS.length];
  const initial = name?.trim()?.[0]?.toUpperCase();
  return (
    <svg width={size} height={size} viewBox="0 0 48 48" className={cx("shrink-0 rounded-full border-2 border-black", className)} role="img" aria-label={label ?? "avatar"}>
      <rect width="48" height="48" fill={a.bg} />
      <g fill={a.fg} opacity={initial ? 0.35 : 0.9}>
        {a.shape === "diamond" && <path d="M24 6 38 24 24 42 10 24z" />}
        {a.shape === "zigzag" && <path d="M0 18l8-8 8 8 8-8 8 8 8-8 8 8v6l-8-8-8 8-8-8-8 8-8-8-8 8zM0 34l8-8 8 8 8-8 8 8 8-8 8 8v6l-8-8-8 8-8-8-8 8-8-8-8 8z" />}
        {a.shape === "circles" && (
          <>
            <circle cx="12" cy="12" r="6" />
            <circle cx="36" cy="12" r="6" />
            <circle cx="12" cy="36" r="6" />
            <circle cx="36" cy="36" r="6" />
            <circle cx="24" cy="24" r="6" />
          </>
        )}
        {a.shape === "triangles" && <path d="M0 48 12 28 24 48zM24 48 36 28 48 48zM12 20 24 0 36 20z" />}
        {a.shape === "stripes" && <path d="M0 8h48v6H0zM0 21h48v6H0zM0 34h48v6H0z" />}
      </g>
      {initial && (
        <text x="24" y="31" textAnchor="middle" fontFamily="Fredoka, system-ui, sans-serif" fontWeight="700" fontSize="20" fill={a.fg}>
          {initial}
        </text>
      )}
    </svg>
  );
}
