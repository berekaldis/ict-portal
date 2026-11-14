import TelecomHeaderNav from '@/components/telecom/TelecomHeaderNav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { usePermission } from '@/hooks/user-permissions';
import AppLayout from '@/layouts/app-layout';
import { Head, router, useForm } from '@inertiajs/react';
import { Building2, Edit, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

type Provider = {
    id: number;
    name: string;
    code?: string | null;
    support_contact?: string | null;
    is_active: boolean;
    notes?: string | null;
    phone_numbers_count?: number;
    broadbands_count?: number;
};

type PageProps = {
    providers: Provider[];
    filters: { search?: string };
    flash?: { success?: string; error?: string };
};

export default function ProvidersIndex({ providers = [], filters = {}, flash }: PageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingProvider, setEditingProvider] = useState<Provider | null>(null);
    const { can } = usePermission();

    const { data, setData, post, put, processing, reset, errors } = useForm<{
        name: string;
        code: string;
        support_contact: string;
        is_active: boolean;
        notes: string;
    }>({
        name: '',
        code: '',
        support_contact: '',
        is_active: true,
        notes: '',
    });

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    function handleOpenAdd() {
        setEditingProvider(null);
        reset();
        setIsModalOpen(true);
    }

    function handleOpenEdit(provider: Provider) {
        setEditingProvider(provider);
        setData({
            name: provider.name,
            code: provider.code ?? '',
            support_contact: provider.support_contact ?? '',
            is_active: provider.is_active,
            notes: provider.notes ?? '',
        });
        setIsModalOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editingProvider) {
            put(`/telecom/providers/${editingProvider.id}`, {
                onSuccess: () => {
                    toast.success('Telecom provider updated');
                    setIsModalOpen(false);
                    reset();
                },
            });
        } else {
            post('/telecom/providers', {
                onSuccess: () => {
                    toast.success('Telecom provider created');
                    setIsModalOpen(false);
                    reset();
                },
            });
        }
    }

    function handleDelete(provider: Provider) {
        if ((provider.phone_numbers_count ?? 0) > 0 || (provider.broadbands_count ?? 0) > 0) {
            toast.error('Cannot delete provider with associated active lines or broadbands.');
            return;
        }

        if (!confirm(`Delete telecom provider "${provider.name}"?`)) return;

        router.delete(`/telecom/providers/${provider.id}`, {
            onSuccess: () => toast.success('Provider deleted'),
            onError: () => toast.error('Failed to delete provider'),
        });
    }

    const filteredProviders = providers.filter((p) => {
        if (!search) return true;
        const q = search.toLowerCase();
        return (
            p.name.toLowerCase().includes(q) ||
            (p.code && p.code.toLowerCase().includes(q)) ||
            (p.support_contact && p.support_contact.toLowerCase().includes(q))
        );
    });

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Telecom Management', href: '/telecom/dashboard' },
                { title: 'Service Providers', href: '/telecom/providers' },
            ]}
        >
            <Head title="Telecom Service Providers" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6 max-w-7xl">
                {/* Unified Header Navigation */}
                <TelecomHeaderNav
                    title="Service Providers"
                    description="Manage telecom partners (Ethio Telecom, Safaricom, etc.) and support contacts"
                >
                    {can('telecom.providers.manage') && (
                        <Button size="sm" onClick={handleOpenAdd} className="gap-1.5 h-8 text-xs">
                            <Plus className="h-3.5 w-3.5" /> Add Provider
                        </Button>
                    )}
                </TelecomHeaderNav>

                <Card className="border shadow-none">
                    <div className="p-4 border-b flex items-center justify-between gap-4">
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search provider name, code, contact..."
                            className="max-w-sm h-9 text-xs"
                        />
                        <span className="text-xs text-muted-foreground">{filteredProviders.length} Providers</span>
                    </div>

                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="text-xs">Provider Name</TableHead>
                                    <TableHead className="text-xs">Code</TableHead>
                                    <TableHead className="text-xs">Support Contact</TableHead>
                                    <TableHead className="text-xs text-center">Phone Lines</TableHead>
                                    <TableHead className="text-xs text-center">Broadbands</TableHead>
                                    <TableHead className="text-xs">Status</TableHead>
                                    <TableHead className="text-xs text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredProviders.map((p) => (
                                    <TableRow key={p.id}>
                                        <TableCell className="text-xs font-medium">{p.name}</TableCell>
                                        <TableCell className="text-xs font-mono text-muted-foreground">{p.code ?? '-'}</TableCell>
                                        <TableCell className="text-xs">{p.support_contact ?? '-'}</TableCell>
                                        <TableCell className="text-xs text-center font-mono">{p.phone_numbers_count ?? 0}</TableCell>
                                        <TableCell className="text-xs text-center font-mono">{p.broadbands_count ?? 0}</TableCell>
                                        <TableCell className="text-xs">
                                            <Badge variant={p.is_active ? 'default' : 'secondary'} className="text-xs font-normal">
                                                {p.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right text-xs">
                                            <div className="flex items-center justify-end gap-1">
                                                {can('telecom.providers.manage') && (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-7 w-7 text-muted-foreground hover:text-foreground"
                                                            onClick={() => handleOpenEdit(p)}
                                                            title="Edit"
                                                        >
                                                            <Edit className="h-3.5 w-3.5" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-7 w-7 text-muted-foreground hover:text-destructive"
                                                            onClick={() => handleDelete(p)}
                                                            title="Delete"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {filteredProviders.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-8 text-xs text-muted-foreground">
                                            No telecom providers found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            {/* Add / Edit Dialog */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent
                    onPointerDownOutside={(e) => e.preventDefault()}
                    onInteractOutside={(e) => e.preventDefault()}
                    onEscapeKeyDown={(e) => e.preventDefault()}
                >
                    <DialogHeader>
                        <DialogTitle>{editingProvider ? 'Edit Telecom Provider' : 'Add Telecom Provider'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 py-2">
                        <div>
                            <Label htmlFor="name">Provider Name <span className="text-rose-500">*</span></Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="e.g. Ethio Telecom, Safaricom"
                                required
                            />
                            {errors.name && <p className="text-xs text-rose-500 mt-1">{errors.name}</p>}
                        </div>

                        <div>
                            <Label htmlFor="code">Code Identifier</Label>
                            <Input
                                id="code"
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value)}
                                placeholder="e.g. ETHIO_TELECOM"
                            />
                        </div>

                        <div>
                            <Label htmlFor="support_contact">Support Contact Info</Label>
                            <Input
                                id="support_contact"
                                value={data.support_contact}
                                onChange={(e) => setData('support_contact', e.target.value)}
                                placeholder="Phone, email, or helpline shortcode"
                            />
                        </div>

                        <div className="flex items-center justify-between">
                            <Label htmlFor="is_active">Is Active Provider?</Label>
                            <Switch
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(val) => setData('is_active', val)}
                            />
                        </div>

                        <div>
                            <Label htmlFor="notes">Notes</Label>
                            <Textarea
                                id="notes"
                                rows={2}
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                            />
                        </div>

                        <DialogFooter className="pt-4">
                            <Button type="button" variant="outline" onClick={() => setIsModalOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {editingProvider ? 'Update Provider' : 'Save Provider'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
