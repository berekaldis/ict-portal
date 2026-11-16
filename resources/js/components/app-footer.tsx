import React from 'react';

export function AppFooter() {
    return (
        <footer className="sticky bottom-0 z-10 bg-background/95 backdrop-blur-xs border-t border-border/40 py-2.5 px-4 md:px-6 mt-auto text-xs text-muted-foreground select-none">
            <div className="flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px]">
                <div className="flex items-center gap-2">
                    <span className="font-semibold text-foreground">Kaldi's Coffee ICT Portal</span>
                    <span className="text-border">•</span>
                    <span>© {new Date().getFullYear()} Kaldi's Bunna. All rights reserved.</span>
                </div>
                <div className="flex items-center gap-3 text-muted-foreground">
                    <a
                        href="tel:+251116616161"
                        className="hover:text-foreground transition-colors"
                        title="ICT Support Hotline"
                    >
                        Hotline: <span className="font-medium text-foreground">+251 11 661 6161</span>
                    </a>
                    <span className="text-border">•</span>
                    <a
                        href="mailto:support.ict@kaldisbunna.et"
                        className="hover:text-foreground transition-colors"
                        title="ICT Support Email"
                    >
                        Support: <span className="font-medium text-foreground">support.ict@kaldisbunna.et</span>
                    </a>
                </div>
            </div>
        </footer>
    );
}
