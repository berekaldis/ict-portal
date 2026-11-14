import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Laptop, Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';

export function AppearanceDropdown() {
    const [theme, setTheme] = useState<'light' | 'dark' | 'system'>('system');

    useEffect(() => {
        const saved = localStorage.getItem('theme') as 'light' | 'dark' | 'system' || 'system';
        setTheme(saved);
        applyTheme(saved);
    }, []);

    const applyTheme = (t: 'light' | 'dark' | 'system') => {
        const root = document.documentElement;
        if (t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
        localStorage.setItem('theme', t);
        setTheme(t);
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground">
                    <Sun className="h-4 w-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
                    <Moon className="absolute h-4 w-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
                    <span className="sr-only">Toggle theme</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => applyTheme('light')} className="cursor-pointer">
                    <Sun className="mr-2 h-4 w-4" /> Light
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => applyTheme('dark')} className="cursor-pointer">
                    <Moon className="mr-2 h-4 w-4" /> Dark
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => applyTheme('system')} className="cursor-pointer">
                    <Laptop className="mr-2 h-4 w-4" /> System
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
