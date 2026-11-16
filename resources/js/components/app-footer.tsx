import { Link, usePage } from '@inertiajs/react';
import { Coffee, Mail, Phone, ShieldCheck, User } from 'lucide-react';
import React from 'react';

export function AppFooter() {
    const { props } = usePage<any>();
    const user = props.auth?.user;
    const roles: string[] = props.auth?.roles || [];

    const primaryRole = roles[0] || user?.role || 'Staff';

    return (
        <footer className="border-t border-border/50 bg-card/60 backdrop-blur-xs py-3.5 px-4 md:px-6 mt-auto text-xs text-muted-foreground select-none">
            <div className="flex flex-col md:flex-row items-center justify-between gap-3">
                {/* Brand, Copyright & Health */}
                <div className="flex flex-wrap items-center justify-center md:justify-start gap-2 sm:gap-3 text-center md:text-left">
                    <div className="flex items-center gap-1.5 font-semibold text-foreground">
                        <Coffee className="h-4 w-4 text-amber-600 dark:text-amber-500" />
                        <span>Kaldi's Coffee ICT Portal</span>
                    </div>
                    <span className="hidden sm:inline text-border">•</span>
                    <span>© {new Date().getFullYear()} Kaldi's Bunna. All rights reserved.</span>
                    <span className="hidden md:inline text-border">•</span>
                    <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        System Active
                    </span>
                </div>

                {/* Support, Version & Active User */}
                <div className="flex flex-wrap items-center justify-center md:justify-end gap-3 sm:gap-4 text-[11px]">
                    <a
                        href="tel:+251116616161"
                        className="flex items-center gap-1 text-muted-foreground hover:text-foreground transition-colors"
                        title="ICT Support Hotline"
                    >
                        <Phone className="h-3 w-3 text-amber-600 dark:text-amber-400" />
                        <span>+251 11 661 6161</span>
                    </a>
                    <span className="text-border">•</span>
                    <a
                        href="mailto:support.ict@kaldisbunna.et"
                        className="flex items-center gap-1 text-muted-foreground hover:text-foreground transition-colors"
                        title="ICT Support Email"
                    >
                        <Mail className="h-3 w-3 text-amber-600 dark:text-amber-400" />
                        <span>support.ict@kaldisbunna.et</span>
                    </a>
                    {user && (
                        <>
                            <span className="text-border hidden sm:inline">•</span>
                            <span className="hidden sm:inline-flex items-center gap-1 text-muted-foreground">
                                <User className="h-3 w-3 text-amber-600 dark:text-amber-400" />
                                <span>{user.name || user.username}</span>
                                <span className="text-[10px] text-muted-foreground/80">({primaryRole})</span>
                            </span>
                        </>
                    )}
                    <span className="text-border">•</span>
                    <span className="font-mono text-[10px] bg-muted/80 text-muted-foreground px-1.5 py-0.5 rounded border border-border/60">
                        v2.4
                    </span>
                </div>
            </div>
        </footer>
    );
}
