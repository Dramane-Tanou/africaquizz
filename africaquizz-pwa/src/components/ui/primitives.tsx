import { forwardRef, type ButtonHTMLAttributes, type InputHTMLAttributes, type ReactNode } from "react";
import { Link, type LinkProps } from "react-router-dom";

export function cx(...c: (string | false | null | undefined)[]) {
  return c.filter(Boolean).join(" ");
}

type Variant = "primary" | "secondary" | "ghost" | "danger" | "green" | "dark";

const variants: Record<Variant, string> = {
  primary: "bg-brand-gold text-black border-2 border-black shadow-flat hover:bg-brand-gold-dark",
  green: "bg-brand-green text-black border-2 border-black shadow-flat hover:bg-brand-green-dark",
  secondary: "bg-surface text-ink border-2 border-black dark:border-line shadow-flat hover:bg-bg",
  dark: "bg-black text-white border-2 border-black shadow-[4px_4px_0_0_rgba(0,0,0,0.35)] hover:bg-black/85",
  ghost: "bg-transparent text-ink hover:bg-ink/5",
  danger: "bg-danger text-white border-2 border-black shadow-flat hover:opacity-90",
};

const base =
  "inline-flex min-h-[48px] items-center justify-center gap-2 rounded-bubble px-5 py-3 font-title text-lg font-semibold " +
  "transition-[transform,box-shadow,background-color] duration-150 active:translate-x-[2px] active:translate-y-[2px] active:shadow-flat-sm " +
  "disabled:cursor-not-allowed disabled:opacity-50 disabled:active:translate-x-0 disabled:active:translate-y-0";

interface BtnProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: Variant;
  block?: boolean;
  loading?: boolean;
}

export const Button = forwardRef<HTMLButtonElement, BtnProps>(function Button(
  { variant = "primary", block, loading, className, children, disabled, ...rest },
  ref,
) {
  return (
    <button
      ref={ref}
      className={cx(base, variants[variant], block && "w-full", className)}
      disabled={disabled || loading}
      aria-busy={loading || undefined}
      {...rest}
    >
      {loading && <Spinner small />}
      {children}
    </button>
  );
});

export function ButtonLink({
  variant = "primary",
  block,
  className,
  ...rest
}: LinkProps & { variant?: Variant; block?: boolean }) {
  return <Link className={cx(base, variants[variant], block && "w-full", className)} {...rest} />;
}

export function Card({ className, children, as: As = "div" }: { className?: string; children: ReactNode; as?: "div" | "section" | "article" }) {
  return (
    <As className={cx("rounded-bubble border-2 border-black p-5 shadow-flat dark:border-line", !/(^|\s)bg-/.test(className ?? "") && "bg-surface", className)}>
      {children}
    </As>
  );
}

export function Spinner({ small, label }: { small?: boolean; label?: string }) {
  return (
    <span role="status" className="inline-flex items-center gap-2">
      <span
        className={cx(
          "inline-block animate-spin rounded-full border-[3px] border-current border-r-transparent motion-reduce:animate-none",
          small ? "h-4 w-4" : "h-8 w-8",
        )}
        aria-hidden
      />
      <span className={label ? "" : "sr-only"}>{label ?? "…"}</span>
    </span>
  );
}

export function FullPageSpinner({ label }: { label?: string }) {
  return (
    <div className="flex min-h-[50vh] items-center justify-center text-ink-2">
      <Spinner label={label} />
    </div>
  );
}

export function Skeleton({ className }: { className?: string }) {
  return <div className={cx("animate-pulse rounded-bubble bg-ink/10 motion-reduce:animate-none", className)} aria-hidden />;
}

export function ProgressBar({ value, className, color = "bg-brand-gold", label }: { value: number; className?: string; color?: string; label?: string }) {
  const v = Math.max(0, Math.min(100, value));
  return (
    <div
      className={cx("h-3 w-full overflow-hidden rounded-full border-2 border-black bg-surface dark:border-line", className)}
      role="progressbar"
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={Math.round(v)}
      aria-label={label}
    >
      <div className={cx("h-full rounded-full transition-[width] duration-300", color)} style={{ width: `${v}%` }} />
    </div>
  );
}

type Tone = "success" | "danger" | "warning" | "info";
const tones: Record<Tone, string> = {
  success: "bg-success-soft text-success border-success/40",
  danger: "bg-danger-soft text-danger border-danger/40",
  warning: "bg-warning-soft text-warning border-warning/40",
  info: "bg-info-soft text-info border-info/40",
};

export function Alert({ tone = "info", children, action }: { tone?: Tone; children: ReactNode; action?: ReactNode }) {
  return (
    <div role={tone === "danger" ? "alert" : "status"} className={cx("flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm font-semibold", tones[tone])}>
      <div className="flex-1">{children}</div>
      {action}
    </div>
  );
}

interface FieldProps extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  hint?: string;
}

export const Field = forwardRef<HTMLInputElement, FieldProps>(function Field({ label, error, hint, id, className, ...rest }, ref) {
  const fid = id ?? rest.name;
  return (
    <div>
      <label htmlFor={fid} className="mb-1 block text-sm font-bold text-ink">
        {label}
      </label>
      <input
        ref={ref}
        id={fid}
        aria-invalid={!!error || undefined}
        aria-describedby={error ? `${fid}-err` : hint ? `${fid}-hint` : undefined}
        className={cx(
          "min-h-[48px] w-full rounded-2xl border-2 bg-surface px-4 py-3 text-base text-ink outline-none transition-colors",
          "placeholder:text-ink-2/70 focus:border-brand-gold-dark focus:ring-2 focus:ring-brand-gold/40",
          error ? "border-danger" : "border-line",
          className,
        )}
        {...rest}
      />
      {hint && !error && (
        <span id={`${fid}-hint`} className="mt-1 block text-xs text-ink-2">
          {hint}
        </span>
      )}
      {error && (
        <span id={`${fid}-err`} className="mt-1 block text-xs font-semibold text-danger">
          {error}
        </span>
      )}
    </div>
  );
});

export function Segmented<T extends string>({
  options,
  value,
  onChange,
  label,
}: {
  options: { value: T; label: string }[];
  value: T;
  onChange: (v: T) => void;
  label: string;
}) {
  return (
    <div role="tablist" aria-label={label} className="flex gap-1 overflow-x-auto rounded-2xl border-2 border-line bg-surface p-1">
      {options.map((o) => (
        <button
          key={o.value}
          role="tab"
          aria-selected={o.value === value}
          onClick={() => onChange(o.value)}
          className={cx(
            "min-h-[40px] flex-1 whitespace-nowrap rounded-xl px-3 text-sm font-bold transition-colors",
            o.value === value ? "bg-brand-gold text-black" : "text-ink-2 hover:bg-ink/5",
          )}
        >
          {o.label}
        </button>
      ))}
    </div>
  );
}

export function Toggle({ checked, onChange, label, hint }: { checked: boolean; onChange: (v: boolean) => void; label: string; hint?: string }) {
  return (
    <label className="flex min-h-[48px] cursor-pointer items-center justify-between gap-4">
      <span>
        <span className="block font-bold">{label}</span>
        {hint && <span className="block text-xs text-ink-2">{hint}</span>}
      </span>
      <input type="checkbox" className="peer sr-only" checked={checked} onChange={(e) => onChange(e.target.checked)} />
      <span
        aria-hidden
        className="relative h-7 w-12 shrink-0 rounded-full border-2 border-black bg-line transition-colors peer-checked:bg-brand-green peer-focus-visible:ring-2 peer-focus-visible:ring-brand-gold after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5 dark:border-line"
      />
    </label>
  );
}

export function PageTitle({ children, sub }: { children: ReactNode; sub?: ReactNode }) {
  return (
    <header className="mb-5">
      <h1 className="font-title text-3xl font-bold text-ink">{children}</h1>
      {sub && <p className="mt-1 text-ink-2">{sub}</p>}
    </header>
  );
}
