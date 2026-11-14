import * as React from 'react';
import { Check, ChevronsUpDown, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';

export type MultiSelectOption = {
    id: string | number;
    name: string;
} | string;

interface MultiSearchableSelectProps {
    options: MultiSelectOption[];
    value: string[];
    onValueChange: (value: string[]) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    className?: string;
    disabled?: boolean;
}

export function MultiSearchableSelect({
    options = [],
    value = [],
    onValueChange,
    placeholder = 'Select items...',
    searchPlaceholder = 'Search items...',
    emptyText = 'No options found.',
    className = 'w-full',
    disabled = false,
}: MultiSearchableSelectProps) {
    const [open, setOpen] = React.useState(false);

    const normalizedOptions = React.useMemo(() => {
        return (options || []).map((opt) => {
            if (typeof opt === 'string') {
                return { id: opt, name: opt };
            }
            return { id: String(opt.id), name: opt.name };
        });
    }, [options]);

    const toggleOption = (optionName: string) => {
        if (value.includes(optionName)) {
            onValueChange(value.filter((val) => val !== optionName));
        } else {
            onValueChange([...value, optionName]);
        }
    };

    const removeOption = (optionName: string, e: React.MouseEvent) => {
        e.stopPropagation();
        onValueChange(value.filter((val) => val !== optionName));
    };

    return (
        <div className={cn('space-y-2', className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        disabled={disabled}
                        className="w-full justify-between font-normal text-left bg-background border-input text-foreground min-h-[40px] h-auto py-2 hover:bg-muted/30"
                    >
                        <span className="truncate text-muted-foreground">
                            {value.length === 0 ? placeholder : `${value.length} selected`}
                        </span>
                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[320px] p-0 z-[9999]" align="start">
                    <Command>
                        <CommandInput placeholder={searchPlaceholder} />
                        <CommandList>
                            <CommandEmpty>{emptyText}</CommandEmpty>
                            <CommandGroup className="max-h-[220px] overflow-y-auto">
                                {normalizedOptions.map((opt) => {
                                    const isSelected = value.includes(opt.name);
                                    return (
                                        <CommandItem
                                            key={opt.id}
                                            value={`${opt.id} ${opt.name}`}
                                            onSelect={() => toggleOption(opt.name)}
                                            className="cursor-pointer"
                                        >
                                            <Check
                                                className={cn(
                                                    'mr-2 h-4 w-4 text-primary',
                                                    isSelected ? 'opacity-100' : 'opacity-0'
                                                )}
                                            />
                                            <span className="truncate">{opt.name}</span>
                                        </CommandItem>
                                    );
                                })}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {/* Selected Tags Badges */}
            {value.length > 0 && (
                <div className="flex flex-wrap gap-1.5 pt-1">
                    {value.map((item) => (
                        <Badge
                            key={item}
                            variant="secondary"
                            className="bg-primary/10 text-primary py-1 pl-2.5 pr-1.5 gap-1 font-medium text-xs border border-primary/20"
                        >
                            {item}
                            <button
                                type="button"
                                onClick={(e) => removeOption(item, e)}
                                className="rounded-full hover:bg-primary/20 p-0.5 transition-colors"
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    ))}
                </div>
            )}
        </div>
    );
}
