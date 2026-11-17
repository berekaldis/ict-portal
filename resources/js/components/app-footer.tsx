import React from 'react';

export function AppFooter() {
    return (
        <footer className="mt-auto border-t border-border/40 py-3 px-4 md:px-6 bg-background text-xs text-muted-foreground select-none">
            <div className="flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px]">
                <div className="flex items-center gap-2">
                    <span className="font-semibold text-foreground">Kaldi's Coffee ICT Portal</span>
                    <span className="text-border">•</span>
                    <span>© {new Date().getFullYear()} Kaldi's Bunna. All rights reserved.</span>
                </div>
                <div className="flex items-center gap-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-primary/10 text-primary border border-primary/20">
                        Developed by IT Department
                    </span>
                </div>
            </div>
        </footer>
    );
}
