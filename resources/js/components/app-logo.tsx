import AppLogoIcon from '@/components/app-logo-icon';

export function AppLogo() {
    return (
        <div className="flex items-center gap-2.5 px-2 py-1.5 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-background p-1 shadow-xs border border-border/70 overflow-hidden">
                <AppLogoIcon className="h-7 w-7" />
            </div>
            <div className="flex flex-col text-left leading-tight min-w-0 group-data-[collapsible=icon]:hidden">
                <span className="font-bold tracking-tight text-sm text-foreground truncate">Kaldi&apos;s Coffee</span>
                <span className="text-[10px] text-muted-foreground uppercase font-medium tracking-wider">ICT Portal</span>
            </div>
        </div>
    );
}

export default AppLogo;
