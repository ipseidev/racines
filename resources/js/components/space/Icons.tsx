import type { PropsWithChildren, SVGProps } from 'react';

/*
 * Les icônes de l'espace : un trait, la couleur du texte, jamais seules. Chaque
 * bouton qui en porte une a aussi un libellé, lu ou visible.
 */
type IconProps = SVGProps<SVGSVGElement>;

function Icon({ children, ...props }: PropsWithChildren<IconProps>) {
    return (
        <svg
            width={20}
            height={20}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
            {...props}
        >
            {children}
        </svg>
    );
}

export function ArrowUp(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M12 19V5" />
            <path d="m5 12 7-7 7 7" />
        </Icon>
    );
}

export function ArrowDown(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M12 5v14" />
            <path d="m19 12-7 7-7-7" />
        </Icon>
    );
}

export function ToTop(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M12 20V9" />
            <path d="m6 15 6-6 6 6" />
            <path d="M5 4h14" />
        </Icon>
    );
}

export function Copy(props: IconProps) {
    return (
        <Icon {...props}>
            <rect x="9" y="9" width="12" height="12" rx="2" />
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
        </Icon>
    );
}

export function Check(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="m5 12 5 5L20 7" />
        </Icon>
    );
}

export function External(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M14 4h6v6" />
            <path d="M20 4 10 14" />
            <path d="M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5" />
        </Icon>
    );
}

export function Plus(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M12 5v14" />
            <path d="M5 12h14" />
        </Icon>
    );
}

export function Trash(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M3 6h18" />
            <path d="M8 6V4h8v2" />
            <path d="m6 6 1 14h10l1-14" />
        </Icon>
    );
}

export function Refresh(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M21 12a9 9 0 1 1-3-6.7" />
            <path d="M21 3v6h-6" />
        </Icon>
    );
}

export function Chevron(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="m6 9 6 6 6-6" />
        </Icon>
    );
}

export function Message(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        </Icon>
    );
}

export function Send(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="m22 2-7 20-4-9-9-4z" />
            <path d="M22 2 11 13" />
        </Icon>
    );
}

export function Headphones(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M3 18v-6a9 9 0 0 1 18 0v6" />
            <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" />
        </Icon>
    );
}

export function Pause(props: IconProps) {
    return (
        <Icon {...props}>
            <rect x="6" y="4" width="4" height="16" rx="1" />
            <rect x="14" y="4" width="4" height="16" rx="1" />
        </Icon>
    );
}
