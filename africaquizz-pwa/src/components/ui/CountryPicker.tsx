import { useId, useMemo, useState } from "react";
import { useTranslation } from "react-i18next";
import type { Country } from "../../types/api";
import { Flag, countryName } from "./Flag";
import { cx } from "./primitives";

const fold = (s: string) => s.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase();

/** Choix du pays avec recherche et drapeaux ; les pays africains sont proposés en premier. */
export function CountryPicker({
  countries,
  value,
  onChange,
  label,
  hint,
  error,
  disabled,
}: {
  countries: Country[];
  value: string | null;
  onChange: (id: string) => void;
  label: string;
  hint?: string;
  error?: string;
  disabled?: boolean;
}) {
  const { t, i18n } = useTranslation();
  const locale = i18n.resolvedLanguage ?? "fr";
  const id = useId();
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");

  const named = useMemo(
    () =>
      countries
        .map((c) => ({ ...c, name: countryName(c.iso2, locale) }))
        .sort((a, b) => a.name.localeCompare(b.name, locale)),
    [countries, locale],
  );
  const selected = named.find((c) => c.id === value);
  const q = fold(query.trim());
  const matches = q ? named.filter((c) => fold(c.name).includes(q) || c.iso2.toLowerCase() === q) : named;
  const groups = [
    { key: "africa", title: t("country.africa"), items: matches.filter((c) => c.is_african) },
    { key: "world", title: t("country.world"), items: matches.filter((c) => !c.is_african) },
  ].filter((g) => g.items.length);

  const showList = open || !selected;

  return (
    <div>
      <span id={`${id}-label`} className="mb-1 block text-sm font-bold text-ink">
        {label}
      </span>
      {selected && !open ? (
        <button
          type="button"
          disabled={disabled}
          onClick={() => setOpen(true)}
          aria-labelledby={`${id}-label`}
          className="flex min-h-[48px] w-full items-center gap-3 rounded-2xl border-2 border-line bg-surface px-4 py-3 text-left text-base font-semibold text-ink hover:border-brand-gold-dark disabled:opacity-60"
        >
          <Flag iso2={selected.iso2} size={20} />
          <span className="flex-1">{selected.name}</span>
          <span className="text-sm font-bold text-ink-2 underline underline-offset-4">{t("country.change")}</span>
        </button>
      ) : null}
      {showList && (
        <div className={cx("rounded-2xl border-2 bg-surface", error ? "border-danger" : "border-line")}>
          <input
            type="search"
            value={query}
            disabled={disabled}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={t("country.search")}
            aria-labelledby={`${id}-label`}
            aria-controls={`${id}-list`}
            className="min-h-[48px] w-full rounded-t-2xl border-b-2 border-line bg-transparent px-4 py-3 text-base text-ink outline-none placeholder:text-ink-2/70 focus:ring-2 focus:ring-brand-gold/40"
          />
          <div id={`${id}-list`} role="listbox" aria-labelledby={`${id}-label`} className="max-h-64 overflow-y-auto p-1">
            {groups.length === 0 && <p className="px-3 py-4 text-sm text-ink-2">{t("country.noMatch")}</p>}
            {groups.map((g) => (
              <div key={g.key} role="group" aria-label={g.title}>
                <p className="sticky top-0 bg-surface px-3 pb-1 pt-2 text-xs font-bold uppercase tracking-wide text-ink-2">{g.title}</p>
                {g.items.map((c) => (
                  <button
                    key={c.id}
                    type="button"
                    role="option"
                    aria-selected={c.id === value}
                    disabled={disabled}
                    onClick={() => {
                      onChange(c.id);
                      setOpen(false);
                      setQuery("");
                    }}
                    className={cx(
                      "flex min-h-[44px] w-full items-center gap-3 rounded-xl px-3 py-2 text-left font-semibold",
                      c.id === value ? "bg-brand-gold/30" : "hover:bg-ink/5",
                    )}
                  >
                    <Flag iso2={c.iso2} size={18} />
                    <span>{c.name}</span>
                  </button>
                ))}
              </div>
            ))}
          </div>
        </div>
      )}
      {hint && !error && <span className="mt-1 block text-xs text-ink-2">{hint}</span>}
      {error && <span className="mt-1 block text-xs font-semibold text-danger">{error}</span>}
    </div>
  );
}
