import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    Download,
    Edit2,
    Filter,
    HardDrive,
    PlusCircle,
    Search,
    Server,
    Trash2,
    X,
} from 'lucide-react';
import React, { useState } from 'react';

interface Asset {
    id: number;
    branch_name: string;
    asset_tag: string | null;
    serial_number: string | null;
    asset_type: string;
    brand: string | null;
    model: string | null;
    specifications: string | null;
    status: 'operational' | 'maintenance' | 'repair' | 'faulty' | 'missing' | 'retired';
    assigned_to: string | null;
    location: string | null;
    ip_address: string | null;
    notes: string | null;
}

interface Stats {
    total: number;
    operational: number;
    maintenance: number;
    faulty: number;
    missing: number;
}

interface Props {
    assets: {
        data: Asset[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    branches: string[];
    stats: Stats;
    assetTypes: string[];
    filters: {
        search?: string;
        branch?: string;
        status?: string;
        asset_type?: string;
    };
}

export default function HardwareIndex({ assets, branches, stats, assetTypes, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedBranch, setSelectedBranch] = useState(filters.branch || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedType, setSelectedType] = useState(filters.asset_type || '');

    // Modal state for Add/Edit
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingAsset, setEditingAsset] = useState<Partial<Asset> | null>(null);

    const applyFilters = () => {
        router.get('/it-surveys/hardware', {
            search,
            branch: selectedBranch,
            status: selectedStatus,
            asset_type: selectedType,
        }, { preserveState: true });
    };

    const resetFilters = () => {
        setSearch('');
        setSelectedBranch('');
        setSelectedStatus('');
        setSelectedType('');
        router.get('/it-surveys/hardware');
    };

    const openAddModal = () => {
        setEditingAsset({
            branch_name: branches[0] || 'Bole Medhanialem',
            asset_type: 'pos',
            status: 'operational',
            asset_tag: '',
            serial_number: '',
            brand: '',
            model: '',
            assigned_to: '',
            ip_address: '',
            notes: '',
        });
        setIsModalOpen(true);
    };

    const openEditModal = (asset: Asset) => {
        setEditingAsset({ ...asset });
        setIsModalOpen(true);
    };

    const handleSave = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingAsset) return;

        router.post('/it-surveys/hardware', editingAsset as any, {
            onSuccess: () => setIsModalOpen(false),
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this hardware asset?')) {
            router.delete(`/it-surveys/hardware/${id}`);
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'operational':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Operational</span>;
            case 'maintenance':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Maintenance</span>;
            case 'repair':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">Under Repair</span>;
            case 'faulty':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Faulty</span>;
            case 'missing':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">Missing</span>;
            default:
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">{status}</span>;
        }
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'IT Survey & Audit', href: '/it-surveys' },
            { title: 'Hardware Asset Inventory', href: '/it-surveys/hardware' },
        ]}>
            <Head title="Branch Hardware Asset Inventory & Audit" />

            <div className="p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <Link
                            href="/it-surveys"
                            className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 mb-2 transition"
                        >
                            <ArrowLeft className="w-3.5 h-3.5" />
                            Back to Audits
                        </Link>
                        <h1 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <HardDrive className="w-5 h-5 text-indigo-600" />
                            Branch Hardware Asset Inventory
                        </h1>
                        <p className="text-sm text-slate-500">Track POS terminals, receipt printers, switches, routers & UPS hardware</p>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <a
                            href="/it-surveys/export/hardware"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            <Download className="w-4 h-4 text-slate-500" />
                            Export CSV
                        </a>
                        <button
                            onClick={openAddModal}
                            className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Add Hardware Asset
                        </button>
                    </div>
                </div>

                {/* KPI Cards */}
                <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div className="text-2xl font-black text-slate-900">{stats.total}</div>
                        <div className="text-xs font-medium text-slate-500">Total Assets</div>
                    </div>
                    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div className="text-2xl font-black text-emerald-700">{stats.operational}</div>
                        <div className="text-xs font-medium text-slate-500">Operational</div>
                    </div>
                    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div className="text-2xl font-black text-amber-700">{stats.maintenance}</div>
                        <div className="text-xs font-medium text-slate-500">Maintenance</div>
                    </div>
                    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div className="text-2xl font-black text-rose-700">{stats.faulty}</div>
                        <div className="text-xs font-medium text-slate-500">Faulty</div>
                    </div>
                    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div className="text-2xl font-black text-purple-700">{stats.missing}</div>
                        <div className="text-xs font-medium text-slate-500">Missing</div>
                    </div>
                </div>

                {/* Filter Bar */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div className="lg:col-span-2 relative">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Search serial #, asset tag, brand, IP..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <select
                                value={selectedBranch}
                                onChange={(e) => setSelectedBranch(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Branches</option>
                                {branches.map((b) => (
                                    <option key={b} value={b}>{b}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <select
                                value={selectedType}
                                onChange={(e) => setSelectedType(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Device Types</option>
                                {assetTypes.map((t) => (
                                    <option key={t} value={t}>{t.toUpperCase()}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex gap-2">
                            <button
                                onClick={applyFilters}
                                className="flex-1 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition"
                            >
                                Filter
                            </button>
                            <button
                                onClick={resetFilters}
                                className="px-3 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                {/* Hardware Assets Table */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-xs uppercase">
                                <tr>
                                    <th className="px-5 py-3.5">Branch</th>
                                    <th className="px-4 py-3.5">Asset Tag / Serial #</th>
                                    <th className="px-4 py-3.5">Device Type</th>
                                    <th className="px-4 py-3.5">Brand / Model</th>
                                    <th className="px-4 py-3.5">IP Address</th>
                                    <th className="px-4 py-3.5">Assigned To</th>
                                    <th className="px-4 py-3.5">Status</th>
                                    <th className="px-4 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {assets.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="text-center py-12 text-slate-400">
                                            No hardware assets registered for the selected filters.
                                        </td>
                                    </tr>
                                ) : (
                                    assets.data.map((a) => (
                                        <tr key={a.id} className="hover:bg-slate-50/80 transition">
                                            <td className="px-5 py-4 font-semibold text-slate-900">{a.branch_name}</td>
                                            <td className="px-4 py-4">
                                                <div className="font-mono text-xs font-bold text-slate-800">{a.asset_tag || '—'}</div>
                                                <div className="font-mono text-[11px] text-slate-400">SN: {a.serial_number || 'N/A'}</div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                                    {a.asset_type}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4 text-xs">
                                                <div className="font-medium text-slate-800">{a.brand || '—'}</div>
                                                <div className="text-slate-500">{a.model || ''}</div>
                                            </td>
                                            <td className="px-4 py-4 text-xs font-mono text-slate-600">
                                                {a.ip_address || '—'}
                                            </td>
                                            <td className="px-4 py-4 text-xs text-slate-700">
                                                {a.assigned_to || '—'}
                                            </td>
                                            <td className="px-4 py-4">
                                                {getStatusBadge(a.status)}
                                            </td>
                                            <td className="px-4 py-4 text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => openEditModal(a)}
                                                        className="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                                        title="Edit Asset"
                                                    >
                                                        <Edit2 className="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(a.id)}
                                                        className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                        title="Delete Asset"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {assets.last_page > 1 && (
                        <div className="flex items-center justify-between p-4 border-t border-slate-100 text-sm text-slate-500">
                            <div>Showing page {assets.current_page} of {assets.last_page} ({assets.total} items)</div>
                            <div className="flex gap-2">
                                {assets.prev_page_url && (
                                    <Link
                                        href={assets.prev_page_url}
                                        className="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {assets.next_page_url && (
                                    <Link
                                        href={assets.next_page_url}
                                        className="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Add / Edit Asset Modal */}
            {isModalOpen && editingAsset && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                        <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                            <h3 className="text-lg font-bold text-slate-900">
                                {editingAsset.id ? 'Edit Hardware Asset' : 'Add New Hardware Asset'}
                            </h3>
                            <button
                                onClick={() => setIsModalOpen(false)}
                                className="text-slate-400 hover:text-slate-700"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSave} className="space-y-4 text-sm">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Branch</label>
                                    <select
                                        value={editingAsset.branch_name || ''}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, branch_name: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                        required
                                    >
                                        {branches.map((b) => (
                                            <option key={b} value={b}>{b}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Device Type</label>
                                    <select
                                        value={editingAsset.asset_type || 'pos'}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, asset_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                        required
                                    >
                                        {assetTypes.map((t) => (
                                            <option key={t} value={t}>{t.toUpperCase()}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Asset Tag</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. AST-BM-POS-01"
                                        value={editingAsset.asset_tag || ''}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, asset_tag: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Serial Number</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. SN-984210"
                                        value={editingAsset.serial_number || ''}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, serial_number: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Brand</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Posiflex, Epson, Cisco"
                                        value={editingAsset.brand || ''}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, brand: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Model</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. XT-3815"
                                        value={editingAsset.model || ''}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, model: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">IP Address</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. 192.168.10.15"
                                        value={editingAsset.ip_address || ''}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, ip_address: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl font-mono text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Operating Status</label>
                                    <select
                                        value={editingAsset.status || 'operational'}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, status: e.target.value as any })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                    >
                                        <option value="operational">Operational</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="repair">Under Repair</option>
                                        <option value="faulty">Faulty</option>
                                        <option value="missing">Missing</option>
                                        <option value="retired">Retired</option>
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Assigned Station</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Counter 1, Kitchen"
                                        value={editingAsset.assigned_to || ''}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, assigned_to: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Physical Location</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Front Cashier Desk"
                                        value={editingAsset.location || ''}
                                        onChange={(e) => setEditingAsset({ ...editingAsset, location: e.target.value })}
                                        className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Notes</label>
                                <textarea
                                    rows={2}
                                    placeholder="Extra specs or issue notes..."
                                    value={editingAsset.notes || ''}
                                    onChange={(e) => setEditingAsset({ ...editingAsset, notes: e.target.value })}
                                    className="w-full px-3 py-2 border border-slate-200 rounded-xl"
                                ></textarea>
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setIsModalOpen(false)}
                                    className="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow"
                                >
                                    Save Asset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
