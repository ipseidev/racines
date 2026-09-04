import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof Link>;

export default function TextLink({
    className = '',
    children,
    ...props
}: Props) {
    return (
        <Link
            className={cn(
                'text-brand hover:decoration-brand decoration-brand-sand underline underline-offset-4 transition-colors duration-300 ease-out',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
