import AppLogoIcon from '@/components/app-logo-icon';
import React from 'react';
import { Toaster } from 'sonner';

interface Props {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AuthLayout({ children, title, description }: Props) {
    return (
        <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-muted/20 relative">
            <div className="absolute inset-0 bg-[radial-gradient(oklch(0.48_0.14_55)_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.03] pointer-events-none" />

            <div className="w-full max-w-md space-y-6 relative z-10">
                <div className="text-center space-y-2">
                    <div className="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-background border border-border/80 shadow-sm p-2 mb-1">
                        <AppLogoIcon className="h-12 w-12" />
                    </div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">
                        {title || "Kaldi's Coffee ICT Portal"}
                    </h1>
                    {description && (
                        <p className="text-sm text-muted-foreground">{description}</p>
                    )}
                </div>

                <div className="bg-card border border-border/70 rounded-xl p-6 sm:p-8 shadow-sm">
                    {children}
                </div>

                <p className="text-center text-xs text-muted-foreground">
                    &copy; {new Date().getFullYear()} Kaldi&apos;s Coffee ICT Department. All rights reserved.
                </p>
            </div>
            <Toaster position="top-right" richColors />
        </div>
    );
}
