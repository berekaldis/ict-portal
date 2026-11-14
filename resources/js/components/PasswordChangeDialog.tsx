import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router, useForm } from '@inertiajs/react';
import { KeyRound, Lock, ShieldAlert } from 'lucide-react';
import React, { useEffect, useState } from 'react';

interface Props {
    open: boolean;
    onClose?: () => void;
}

export function PasswordChangeDialog({ open, onClose }: Props) {
    const [isOpen, setIsOpen] = useState(open);

    useEffect(() => {
        setIsOpen(open);
    }, [open]);

    const { data, setData, post, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsOpen(false);
                reset();
                if (onClose) onClose();
            },
        });
    };

    const handleDismiss = () => {
        setIsOpen(false);
        router.post(route('password.dismiss'));
        if (onClose) onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={(val) => {
            if (!val) handleDismiss();
        }}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="flex items-center gap-2 text-amber-600 dark:text-amber-500 font-semibold mb-1">
                        <ShieldAlert className="h-5 w-5" />
                        <span>Security Setup: Change Password</span>
                    </div>
                    <DialogTitle className="text-xl">Set Personalized Password</DialogTitle>
                    <DialogDescription className="text-xs text-muted-foreground pt-1">
                        You are currently using the default system password (<code className="font-mono bg-muted px-1 py-0.5 rounded text-foreground">admin123</code>) or your account requires a security update.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 pt-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="current_password">Current Password (optional)</Label>
                        <div className="relative">
                            <Lock className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="current_password"
                                type="password"
                                placeholder="Enter current password if known..."
                                className="pl-9"
                                value={data.current_password}
                                onChange={(e) => setData('current_password', e.target.value)}
                            />
                        </div>
                        {errors.current_password && (
                            <p className="text-xs text-destructive">{errors.current_password}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="new_password">New Password</Label>
                        <div className="relative">
                            <KeyRound className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="new_password"
                                type="password"
                                required
                                minLength={4}
                                placeholder="Minimum 4 characters..."
                                className="pl-9"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                            />
                        </div>
                        {errors.password && (
                            <p className="text-xs text-destructive">{errors.password}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="password_confirmation">Confirm New Password</Label>
                        <div className="relative">
                            <KeyRound className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="password_confirmation"
                                type="password"
                                required
                                minLength={4}
                                placeholder="Re-type new password..."
                                className="pl-9"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                            />
                        </div>
                        {errors.password_confirmation && (
                            <p className="text-xs text-destructive">{errors.password_confirmation}</p>
                        )}
                    </div>

                    <DialogFooter className="flex items-center justify-between sm:justify-between pt-2">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={handleDismiss}
                            className="text-xs text-muted-foreground"
                        >
                            Skip for Now
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-amber-600 hover:bg-amber-700 text-white"
                        >
                            {processing ? 'Saving...' : 'Update Password'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
