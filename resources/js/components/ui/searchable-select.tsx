import * as React from 'react';
import { Check, ChevronsUpDown, Search, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Input } from '@/components/ui/input';

export type SelectOption = {
    id: string | number;
    name: string;
} | string;

interface SearchableSelectProps {
    options: SelectOption[];
    value?: string | number;
    onValueChange: (value: string) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    allowAll?: boolean;
    allLabel?: string;
    allValue?: string;
    className?: string;
    disabled?: boolean;
    portalled?: boolean;
    id?: string;
}

export function SearchableSelect({
    options = [],
    value,
    onValueChange,
    placeholder = 'Select option...',
    searchPlaceholder = 'Search...',
    emptyText = 'No option found.',
    allowAll = false,
    allLabel = 'All Options',
    allValue = 'all',
    className = 'w-full',
    disabled = false,
    portalled = false,
    id,
}: SearchableSelectProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');
    const inputRef = React.useRef<HTMLInputElement>(null);

    React.useEffect(() => {
        if (open) {
            const timer = setTimeout(() => {
                inputRef.current?.focus();
            }, 60);
            return () => clearTimeout(timer);
        } else {
            setSearch('');
        }
    }, [open]);

    const normalizedOptions = React.useMemo(() => {
        return (options || []).map((opt) => {
            if (typeof opt === 'string') {
                return { id: opt, name: opt };
            }
            return { id: String(opt.id), name: opt.name };
        });
    }, [options]);

    const stringValue = value !== undefined && value !== null ? String(value) : '';

    const selectedOption = React.useMemo(() => {
        return normalizedOptions.find(
            (opt) => opt.id === stringValue || opt.name === stringValue
        );
    }, [normalizedOptions, stringValue]);

    let displayLabel = placeholder;
    if (allowAll && (stringValue === allValue || stringValue === '')) {
        displayLabel = allLabel;
    } else if (selectedOption) {
        displayLabel = selectedOption.name;
    }

    const filteredOptions = React.useMemo(() => {
        if (!search.trim()) return normalizedOptions;
        const q = search.toLowerCase().trim();
        return normalizedOptions.filter(
            (opt) => opt.name.toLowerCase().includes(q) || opt.id.toLowerCase().includes(q)
        );
    }, [normalizedOptions, search]);

    const MAX_DISPLAY = 150;
    const displayedOptions = React.useMemo(() => {
        return filteredOptions.slice(0, MAX_DISPLAY);
    }, [filteredOptions]);

    const handleSelect = (selectedVal: string) => {
        onValueChange(selectedVal);
        setOpen(false);
        setSearch('');
    };

    return (
        <Popover open={open} onOpenChange={setOpen} modal={false}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    disabled={disabled}
                    className={cn(
                        'justify-between font-normal text-left bg-background border-input text-foreground h-10 px-3 py-2 hover:bg-muted/30 focus:ring-2 focus:ring-ring focus:ring-offset-2',
                        className
                    )}
                >
                    <span className="truncate">{displayLabel}</span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                portalled={portalled}
                className="w-[var(--radix-popover-trigger-width)] min-w-[260px] p-2 z-[9999] bg-popover shadow-md border rounded-md"
                align="start"
                onOpenAutoFocus={(e) => {
                    e.preventDefault();
                    inputRef.current?.focus();
                }}
                onCloseAutoFocus={(e) => {
                    e.preventDefault();
                }}
            >
                <div className="relative mb-2" onClick={(e) => e.stopPropagation()}>
                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground pointer-events-none" />
                    <Input
                        ref={inputRef}
                        type="text"
                        placeholder={searchPlaceholder}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            e.stopPropagation();
                            if (e.key === 'Escape') {
                                setOpen(false);
                            }
                        }}
                        className="pl-8 pr-8 h-9 text-sm"
                    />
                    {search && (
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                setSearch('');
                                inputRef.current?.focus();
                            }}
                            className="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    )}
                </div>
                <div className="max-h-[220px] overflow-y-auto space-y-0.5">
                    {allowAll && (
                        <div
                            role="option"
                            aria-selected={stringValue === allValue || stringValue === ''}
                            onClick={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                handleSelect(allValue);
                            }}
                            onMouseDown={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                handleSelect(allValue);
                            }}
                            className={cn(
                                'flex items-center justify-between px-2.5 py-1.5 rounded-sm text-sm cursor-pointer hover:bg-accent hover:text-accent-foreground select-none',
                                (stringValue === allValue || stringValue === '') && 'bg-accent/50 font-medium'
                            )}
                        >
                            <span>{allLabel}</span>
                            {(stringValue === allValue || stringValue === '') && <Check className="h-4 w-4 text-primary" />}
                        </div>
                    )}

                    {filteredOptions.length === 0 ? (
                        <div className="py-4 text-center text-xs text-muted-foreground">{emptyText}</div>
                    ) : (
                        <>
                            {displayedOptions.map((opt) => {
                                const isSelected = stringValue === opt.id || stringValue === opt.name;
                                return (
                                    <div
                                        key={opt.id}
                                        role="option"
                                        aria-selected={isSelected}
                                        onClick={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            handleSelect(opt.id);
                                        }}
                                        onMouseDown={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            handleSelect(opt.id);
                                        }}
                                        className={cn(
                                            'flex items-center justify-between px-2.5 py-2 rounded-sm text-sm cursor-pointer hover:bg-accent hover:text-accent-foreground select-none transition-colors',
                                            isSelected && 'bg-primary/10 text-primary font-semibold'
                                        )}
                                    >
                                        <span className="truncate">{opt.name}</span>
                                        {isSelected && <Check className="h-4 w-4 text-primary shrink-0" />}
                                    </div>
                                );
                            })}
                            {filteredOptions.length > MAX_DISPLAY && (
                                <div className="py-1.5 px-2 text-center text-[11px] text-muted-foreground bg-muted/30 border-t sticky bottom-0">
                                    Showing first {MAX_DISPLAY} of {filteredOptions.length} results. Type to search...
                                </div>
                            )}
                        </>
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
}
