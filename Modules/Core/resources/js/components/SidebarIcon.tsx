import type { ResolvedIcon } from '../types/navigation';

export interface SidebarIconProps {
    icon: ResolvedIcon | null;
    fallbackClass: string;
    tone: 'active' | 'muted';
    small?: boolean;
}

/**
 * Renders a navigation icon from a server-resolved shape.
 *
 * Inline SVGs come from `ap.*` icon sets that the backend has already
 * normalized to `fill="currentColor"`, so they inherit the wrapping
 * span's text color. Font Awesome icons are rendered by class.
 */
export function SidebarIcon({ icon, fallbackClass, tone, small = false }: SidebarIconProps) {
    const color = tone === 'active' ? 'text-secondary' : 'text-text-subtle';
    const size = small ? 'text-[13px]' : 'text-[15px]';
    const box = small ? 'w-4' : 'w-[18px]';

    if (icon?.type === 'svg') {
        return (
            <span
                className={`inline-flex ${box} items-center justify-center ${color}`}
                aria-hidden
                dangerouslySetInnerHTML={{
                    __html: sizedSvg(icon.markup, small ? 14 : 18),
                }}
            />
        );
    }

    const className = icon?.type === 'class' ? icon.class : fallbackClass;
    return <i className={`${className} ${box} text-center ${size} ${color}`} aria-hidden />;
}

// Force the SVG to the target render size so different source viewBoxes
// don't blow out the sidebar row.
function sizedSvg(markup: string, size: number): string {
    const attrs = `width="${size}" height="${size}"`;
    if (/<svg\b[^>]*\swidth=/i.test(markup)) {
        return markup;
    }
    return markup.replace(/<svg\b/i, `<svg ${attrs}`);
}

export default SidebarIcon;
