export type ResolvedIcon =
    | { type: 'svg'; markup: string }
    | { type: 'class'; class: string };

export interface SidebarPageNode {
    id: number;
    title: string;
    slug: string;
    icon: ResolvedIcon | null;
    parent: number | null;
    isCurrentPage: boolean;
    active: boolean;
    children?: SidebarPageNode[];
}

export interface SidebarDocNode {
    id: number;
    title: string;
    slug: string;
    parent: number | null;
    isCurrentPage: boolean;
    active: boolean;
    children?: SidebarDocNode[];
}

export interface SidebarPackage {
    id: number;
    name: string;
    slug: string;
    icon: ResolvedIcon | null;
    active: boolean;
    homepage: {
        id: number;
        title: string;
        slug: string;
        active: boolean;
    } | null;
    documentation: SidebarDocNode[];
    changelog: {
        id: number;
        title: string;
        slug: string;
        active: boolean;
    } | null;
}

export interface SidebarNavigation {
    pages: SidebarPageNode[];
    packages: SidebarPackage[];
}

export interface TocHeading {
    id: string;
    text: string;
    level: number;
    children?: TocHeading[];
}
