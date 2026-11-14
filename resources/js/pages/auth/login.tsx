import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    Building2,
    Eye,
    EyeOff,
    Info,
    Lock,
    LogIn,
    Shield,
    Sparkles,
    User,
} from 'lucide-react';
import React, { useState } from 'react';

interface Props {
    status?: string;
    currentUser?: {
        name: string;
        role: string;
    } | null;
}

export default function Login({ status, currentUser }: Props) {
    const [showPassword, setShowPassword] = useState(false);

    // Form state
    const { data, setData, post, processing, errors, reset } = useForm({
        login: '',
        username: '',
        password: '',
        remember: true as boolean,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    const selectQuickAccount = (username: string, pass: string = 'admin123') => {
        setData((prev) => ({
            ...prev,
            login: username,
            username: username,
            password: pass,
        }));
    };

    return (
        <AuthLayout
            title="Kaldis Voucher & Support Portal"
            description="Single Sign-On for Branch Requests, Department Approvals & IT Cases"
        >
            <Head title="Sign In - Kaldis ICT" />

            {currentUser && (
                <div className="mb-4 p-3 rounded-lg text-xs font-medium bg-amber-500/10 text-amber-900 dark:text-amber-200 border border-amber-500/20 flex items-center justify-between gap-2">
                    <div>
                        Currently signed in as <span className="font-semibold">{currentUser.name}</span> ({currentUser.role}).
                    </div>
                    <Link
                        href="/dashboard"
                        className="text-xs font-semibold text-primary hover:underline shrink-0"
                    >
                        Go to Dashboard &rarr;
                    </Link>
                </div>
            )}

            {status && (
                <div className="mb-4 p-3 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    {status}
                </div>
            )}

            {errors.login && (
                <div className="mb-4 p-3 rounded-lg text-xs font-medium bg-destructive/10 text-destructive border border-destructive/20 flex items-start gap-2">
                    <Info className="h-4 w-4 shrink-0 mt-0.5" />
                    <span>{errors.login}</span>
                </div>
            )}

            {/* UNIFIED SINGLE LOGIN FORM */}
            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-1.5">
                    <Label htmlFor="login_input" className="text-xs font-semibold">
                        Username or Email
                    </Label>
                    <div className="relative">
                        <User className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="login_input"
                            type="text"
                            autoFocus
                            placeholder="Enter username or email address..."
                            className="pl-9"
                            value={data.login}
                            onChange={(e) => {
                                const val = e.target.value;
                                setData((prev) => ({
                                    ...prev,
                                    login: val,
                                    username: val,
                                }));
                            }}
                            required
                        />
                    </div>
                    {errors.username && (
                        <p className="text-xs text-destructive">{errors.username}</p>
                    )}
                </div>

                <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="password_input" className="text-xs font-semibold">
                            Password
                        </Label>
                    </div>
                    <div className="relative">
                        <Lock className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="password_input"
                            type={showPassword ? 'text' : 'password'}
                            placeholder="Enter your password..."
                            className="pl-9 pr-10"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            autoComplete="current-password"
                            required
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute right-3 top-2.5 text-muted-foreground hover:text-foreground focus:outline-none"
                            tabIndex={-1}
                        >
                            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                        </button>
                    </div>
                    {errors.password && (
                        <p className="text-xs text-destructive">{errors.password}</p>
                    )}
                </div>

                <div className="flex items-center justify-between pt-1">
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="remember_me"
                            checked={data.remember}
                            onCheckedChange={(c) => setData('remember', !!c)}
                        />
                        <label
                            htmlFor="remember_me"
                            className="text-xs font-medium text-muted-foreground cursor-pointer"
                        >
                            Keep me signed in
                        </label>
                    </div>

                    <div className="flex items-center gap-1 text-[11px] text-muted-foreground">
                        <Building2 className="h-3 w-3 text-amber-600 dark:text-amber-500" />
                        <span>Company System Synced</span>
                    </div>
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="w-full mt-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold shadow-sm"
                >
                    {processing ? (
                        'Signing in...'
                    ) : (
                        <>
                            <LogIn className="mr-2 h-4 w-4" /> Sign In
                        </>
                    )}
                </Button>

                {/* Quick Role Select Chips for Admin & Testing */}
                <div className="pt-3 border-t border-border/50">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-[11px] text-muted-foreground font-medium flex items-center gap-1">
                            <Sparkles className="h-3 w-3 text-amber-600 dark:text-amber-500" /> Quick Account Selector:
                        </span>
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                        {[
                            { u: 'superadmin', label: 'Super Admin', badge: 'Full IT' },
                            { u: 'admin', label: 'Admin', badge: 'Finance' },
                            { u: 'finance_admin', label: 'Finance Admin', badge: 'Finance' },
                            { u: 'fb_admin', label: 'F&B Admin', badge: 'F&B' },
                            { u: 'it_admin', label: 'IT Admin', badge: 'Cases & IT' },
                            { u: 'branch_user', label: 'Branch User', badge: 'Bole' },
                        ].map((acc) => (
                            <button
                                key={acc.u}
                                type="button"
                                onClick={() => selectQuickAccount(acc.u)}
                                className={`text-[11px] px-2.5 py-1 rounded-md border transition-all flex items-center gap-1.5 ${
                                    data.login === acc.u
                                        ? 'bg-primary text-primary-foreground border-primary font-semibold shadow-xs'
                                        : 'bg-muted/50 hover:bg-muted text-foreground/80 border-border/60'
                                }`}
                            >
                                <span>{acc.label}</span>
                                <span className="text-[9px] opacity-70 px-1 py-0.2 rounded bg-black/10 dark:bg-white/10">
                                    {acc.badge}
                                </span>
                            </button>
                        ))}
                    </div>
                </div>
            </form>
        </AuthLayout>
    );
}
