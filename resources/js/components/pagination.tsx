import { Link } from '@inertiajs/react';

export default function Pagination({ links }: { links: { url: string | null; label: string; active: boolean }[] }) {
  return (
    <nav className="flex items-center gap-1" aria-label="Paginação">
      {links.map((link, i) => (
        <span key={i}>
          {link.url ? (
            <Link
              href={link.url}
              className={
                link.active
                  ? 'rounded-md bg-gray-900 px-3 py-1.5 text-sm text-white'
                  : 'rounded-md px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100'
              }
              dangerouslySetInnerHTML={{ __html: link.label }}
            />
          ) : (
            <span className="px-3 py-1.5 text-sm text-gray-400" dangerouslySetInnerHTML={{ __html: link.label }} />
          )}
        </span>
      ))}
    </nav>
  );
}
