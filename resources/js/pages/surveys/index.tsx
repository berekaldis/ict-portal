import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    BarChart3,
    CheckCircle2,
    Coffee,
    Download,
    Eye,
    Filter,
    MessageSquare,
    QrCode,
    RefreshCw,
    Search,
    Smile,
    Sparkles,
    Star,
    ThumbsUp,
    Trash2,
    Users,
} from 'lucide-react';
import React, { useState } from 'react';

interface SurveyItem {
    id: number;
    branch: string;
    table_number: string | null;
    q1_frequency: string | null;
    q2_reason: string | null;
    q2_reason_other: string | null;
    q3_spend: string | null;
    q4_orders: string[] | string | null;
    q5_missing_menu: string | null;
    q5_missing_detail: string | null;
    q6_factors: string[] | string | null;
    q7_location_rating: string | null;
    q8_price_rating: string | null;
    q9_improvements: string[] | string | null;
    q10_consider_other_cafe: string | null;
    q11_why_other_cafe: string | null;
    q12_if_branch_closed: string | null;
    q13_importance: string | null;
    q14_waiter_rating: string | null;
    q15_waiter_improvements: string[] | string | null;
    q16_interior_rating: string | null;
    q17_interior_improvements: string[] | string | null;
    q18_waiter_recommendations: string | null;
    q19_service_suggestions: string | null;
    q20_visit_motivation: string | null;
    created_at: string;
}

interface StatItem {
    label: string;
    count: number;
}

interface Props {
    responses: {
        data: SurveyItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    branches: string[];
    selectedBranch: string;
    filters: {
        search: string;
    };
    kpis: {
        total: number;
        waiterSatisfactionPct: number;
        interiorSatisfactionPct: number;
        priceFairPct: number;
    };
    frequencyStats: StatItem[];
    spendStats: StatItem[];
    recentQuotes: {
        id: number;
        branch: string;
        table_number: string | null;
        q19_service_suggestions: string;
        created_at: string;
    }[];
}

export default function SurveyIndexPage({
    responses,
    branches,
    selectedBranch,
    filters,
    kpis,
    frequencyStats,
    spendStats,
    recentQuotes,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedItem, setSelectedItem] = useState<SurveyItem | null>(null);

    const applyFilter = (params: Record<string, string>) => {
        router.get(
            route('surveys.index'),
            {
                branch: selectedBranch,
                search,
                ...params,
            },
            { preserveState: true }
        );
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this customer survey response?')) {
            router.delete(route('surveys.destroy', id));
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Customer Survey', href: '/surveys' }, { title: 'Analytics & Responses' }]}>
            <Head title="Customer Survey & Branch Analytics | Kaldis Coffee" />

            <div className="space-y-6 max-w-7xl mx-auto pb-12">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border/40">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <Coffee className="h-5 w-5 text-amber-600" /> Customer Survey & Branch Analytics
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Customer satisfaction metrics, service feedback, and branch QR performance
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                            <Link href={route('surveys.qr')}>
                                <QrCode className="h-3.5 w-3.5 mr-1 text-amber-600" /> Branch QR Generator
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                            <a href={`/surveys/export?branch=${encodeURIComponent(selectedBranch)}`}>
                                <Download className="h-3.5 w-3.5 mr-1" /> Export CSV (UTF-8)
                            </a>
                        </Button>
                        <Button asChild size="sm" className="h-8 text-xs bg-amber-700 hover:bg-amber-800 text-white">
                            <a href="/survey" target="_blank" rel="noreferrer">
                                <Sparkles className="h-3.5 w-3.5 mr-1" /> Public Survey View
                            </a>
                        </Button>
                    </div>
                </div>

                {/* KPI Metrics */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-border/60 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Total Responses</p>
                                <p className="text-2xl font-bold text-foreground mt-0.5">{kpis.total}</p>
                                <span className="text-[10px] text-muted-foreground">Across all scanned tables</span>
                            </div>
                            <div className="w-10 h-10 rounded-full bg-amber-500/10 text-amber-600 flex items-center justify-center">
                                <MessageSquare className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Staff / Waiter Rating</p>
                                <p className="text-2xl font-bold text-emerald-600 mt-0.5">{kpis.waiterSatisfactionPct}%</p>
                                <span className="text-[10px] text-emerald-600/80 font-medium">Rated Good / Excellent</span>
                            </div>
                            <div className="w-10 h-10 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                                <Smile className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Interior Satisfaction</p>
                                <p className="text-2xl font-bold text-blue-600 mt-0.5">{kpis.interiorSatisfactionPct}%</p>
                                <span className="text-[10px] text-blue-600/80 font-medium">Positive vibe & ambiance</span>
                            </div>
                            <div className="w-10 h-10 rounded-full bg-blue-500/10 text-blue-600 flex items-center justify-center">
                                <Star className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Price Sentiment</p>
                                <p className="text-2xl font-bold text-purple-600 mt-0.5">{kpis.priceFairPct}%</p>
                                <span className="text-[10px] text-purple-600/80 font-medium">Fair / Balanced value</span>
                            </div>
                            <div className="w-10 h-10 rounded-full bg-purple-500/10 text-purple-600 flex items-center justify-center">
                                <ThumbsUp className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter and charts section */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Visual Stats 1: Frequency */}
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-3 px-4 border-b border-border/40">
                            <CardTitle className="text-xs font-bold flex items-center gap-1.5">
                                <BarChart3 className="w-4 h-4 text-amber-600" /> Visit Frequency Distribution
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3">
                            {frequencyStats.map((item) => {
                                const pct = kpis.total > 0 ? Math.round((item.count / kpis.total) * 100) : 0;
                                return (
                                    <div key={item.label} className="space-y-1">
                                        <div className="flex justify-between text-xs">
                                            <span className="text-muted-foreground truncate">{item.label}</span>
                                            <span className="font-semibold">{item.count} ({pct}%)</span>
                                        </div>
                                        <div className="w-full bg-muted rounded-full h-2 overflow-hidden">
                                            <div className="bg-amber-600 h-2 rounded-full" style={{ width: `${pct}%` }} />
                                        </div>
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>

                    {/* Visual Stats 2: Spend Distribution */}
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-3 px-4 border-b border-border/40">
                            <CardTitle className="text-xs font-bold flex items-center gap-1.5">
                                <BarChart3 className="w-4 h-4 text-emerald-600" /> Spend Per Visit Distribution
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3">
                            {spendStats.map((item) => {
                                const pct = kpis.total > 0 ? Math.round((item.count / kpis.total) * 100) : 0;
                                return (
                                    <div key={item.label} className="space-y-1">
                                        <div className="flex justify-between text-xs">
                                            <span className="text-muted-foreground truncate">{item.label}</span>
                                            <span className="font-semibold">{item.count} ({pct}%)</span>
                                        </div>
                                        <div className="w-full bg-muted rounded-full h-2 overflow-hidden">
                                            <div className="bg-emerald-600 h-2 rounded-full" style={{ width: `${pct}%` }} />
                                        </div>
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>

                    {/* Customer Voice Quotes */}
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-3 px-4 border-b border-border/40">
                            <CardTitle className="text-xs font-bold flex items-center gap-1.5">
                                <MessageSquare className="w-4 h-4 text-primary" /> Direct Customer Voice
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 space-y-2.5 max-h-72 overflow-y-auto">
                            {recentQuotes.length === 0 ? (
                                <p className="text-xs text-muted-foreground text-center py-4">No text suggestions recorded yet.</p>
                            ) : (
                                recentQuotes.map((q) => (
                                    <div key={q.id} className="p-2.5 rounded-lg border border-border/50 bg-muted/20 text-xs space-y-1">
                                        <div className="flex justify-between items-center text-[10px] text-muted-foreground">
                                            <Badge variant="outline" className="text-[10px] px-1 py-0">{q.branch}</Badge>
                                            <span>{q.table_number ? `Table ${q.table_number}` : ''}</span>
                                        </div>
                                        <p className="text-foreground italic">"{q.q19_service_suggestions}"</p>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Filter Bar */}
                <div className="bg-card rounded-xl p-3 border border-border/50 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
                    <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Filter className="w-3.5 h-3.5" /> Filter by Branch:
                        </div>
                        <select
                            value={selectedBranch}
                            onChange={(e) => applyFilter({ branch: e.target.value })}
                            className="h-8 text-xs rounded-lg border-border/60 bg-muted/30 px-2.5 font-medium"
                        >
                            <option value="all">All Branches ({responses.total})</option>
                            {branches.map((b) => (
                                <option key={b} value={b}>{b}</option>
                            ))}
                        </select>
                    </div>

                    <div className="relative w-full sm:w-72">
                        <Search className="w-3.5 h-3.5 text-muted-foreground absolute left-2.5 top-2.5" />
                        <Input
                            placeholder="Search branch, table, feedback..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && applyFilter({ search })}
                            className="h-8 text-xs pl-8"
                        />
                    </div>
                </div>

                {/* Responses Data Table */}
                <Card className="border-border/60 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr className="border-b border-border/60 bg-muted/40 font-semibold text-muted-foreground">
                                    <th className="p-3">ID</th>
                                    <th className="p-3">Date</th>
                                    <th className="p-3">Branch</th>
                                    <th className="p-3">Table</th>
                                    <th className="p-3">Frequency</th>
                                    <th className="p-3">Spend</th>
                                    <th className="p-3">Waiter Rating</th>
                                    <th className="p-3">Interior Rating</th>
                                    <th className="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border/30">
                                {responses.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={9} className="p-8 text-center text-muted-foreground">
                                            No survey responses found.
                                        </td>
                                    </tr>
                                ) : (
                                    responses.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-muted/20 transition-colors">
                                            <td className="p-3 font-mono font-medium text-foreground">#{item.id}</td>
                                            <td className="p-3 text-muted-foreground whitespace-nowrap">
                                                {new Date(item.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="p-3 font-medium text-foreground">{item.branch}</td>
                                            <td className="p-3 text-muted-foreground">{item.table_number || '-'}</td>
                                            <td className="p-3 text-muted-foreground">{item.q1_frequency || '-'}</td>
                                            <td className="p-3 text-muted-foreground">{item.q3_spend || '-'}</td>
                                            <td className="p-3">
                                                <Badge
                                                    variant="outline"
                                                    className={`text-[10px] ${
                                                        item.q14_waiter_rating?.includes('ጥሩ')
                                                            ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {item.q14_waiter_rating || 'N/A'}
                                                </Badge>
                                            </td>
                                            <td className="p-3">
                                                <Badge
                                                    variant="outline"
                                                    className={`text-[10px] ${
                                                        item.q16_interior_rating?.includes('ጥሩ')
                                                            ? 'bg-blue-500/10 text-blue-600 border-blue-500/30'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {item.q16_interior_rating || 'N/A'}
                                                </Badge>
                                            </td>
                                            <td className="p-3 text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7"
                                                        onClick={() => setSelectedItem(item)}
                                                        title="View full 20 responses"
                                                    >
                                                        <Eye className="w-3.5 h-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-destructive hover:text-destructive"
                                                        onClick={() => handleDelete(item.id)}
                                                        title="Delete"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {responses.last_page > 1 && (
                        <div className="p-3 border-t border-border/40 flex items-center justify-between text-xs text-muted-foreground">
                            <div>Showing page {responses.current_page} of {responses.last_page}</div>
                            <div className="flex gap-1">
                                {responses.links.map((link, idx) => (
                                    <Button
                                        key={idx}
                                        asChild={!!link.url}
                                        disabled={!link.url}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        className="h-7 text-xs px-2.5"
                                    >
                                        {link.url ? (
                                            <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                        ) : (
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        )}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    )}
                </Card>

                {/* Modal: View Detailed Survey Submission */}
                <Dialog open={!!selectedItem} onOpenChange={() => setSelectedItem(null)}>
                    <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle className="text-base font-bold flex items-center gap-2">
                                <Coffee className="w-4 h-4 text-amber-600" />
                                Customer Survey #{selectedItem?.id} - {selectedItem?.branch}
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Logged on {selectedItem ? new Date(selectedItem.created_at).toLocaleString() : ''}
                                {selectedItem?.table_number ? ` • Table ${selectedItem.table_number}` : ''}
                            </DialogDescription>
                        </DialogHeader>

                        {selectedItem && (
                            <div className="space-y-3 text-xs pt-2 divide-y divide-border/40">
                                <div className="space-y-1">
                                    <span className="font-bold text-muted-foreground">1. የጉብኝት ድግግሞሽ (Frequency):</span>
                                    <p className="text-foreground">{selectedItem.q1_frequency || '-'}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">2. የመጡበት ዋና ምክንያት (Primary Reason):</span>
                                    <p className="text-foreground">{selectedItem.q2_reason} {selectedItem.q2_reason_other ? `(${selectedItem.q2_reason_other})` : ''}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">3. አማካይ ወጪ (Spend):</span>
                                    <p className="text-foreground">{selectedItem.q3_spend || '-'}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">4. የሚታዘዙ ምርቶች (Orders):</span>
                                    <p className="text-foreground">{Array.isArray(selectedItem.q4_orders) ? selectedItem.q4_orders.join(', ') : (selectedItem.q4_orders || '-')}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">5. በሜኑ ላይ እንዲካተት የሚፈለግ (Missing Menu):</span>
                                    <p className="text-foreground">{selectedItem.q5_missing_menu} {selectedItem.q5_missing_detail ? `(${selectedItem.q5_missing_detail})` : ''}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">6. ይህን ቅርንጫፍ የመረጡበት ምክንያት (Factors):</span>
                                    <p className="text-foreground">{Array.isArray(selectedItem.q6_factors) ? selectedItem.q6_factors.join(', ') : (selectedItem.q6_factors || '-')}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">7. የተደራሽነት ደረጃ (Location):</span>
                                    <p className="text-foreground">{selectedItem.q7_location_rating || '-'}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">8. የዋጋ ተመጣጣኝነት (Price):</span>
                                    <p className="text-foreground">{selectedItem.q8_price_rating || '-'}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">9. እንዲሻሻሉ የሚፈለጉ ነገሮች (Improvements):</span>
                                    <p className="text-foreground">{Array.isArray(selectedItem.q9_improvements) ? selectedItem.q9_improvements.join(', ') : (selectedItem.q9_improvements || '-')}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">14. የአገልጋዮች ደረጃ (Waiter Rating):</span>
                                    <p className="text-foreground font-semibold text-emerald-600">{selectedItem.q14_waiter_rating || '-'}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">16. የውስጥ ዲዛይን ግምገማ (Interior Rating):</span>
                                    <p className="text-foreground font-semibold text-blue-600">{selectedItem.q16_interior_rating || '-'}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">19. የአገልግሎት ማሻሻያ አስተያየት (Suggestions):</span>
                                    <p className="text-foreground bg-muted/20 p-2 rounded-lg italic">{selectedItem.q19_service_suggestions || 'ምንም አልተጻፈም'}</p>
                                </div>
                                <div className="pt-2 space-y-1">
                                    <span className="font-bold text-muted-foreground">20. ደጋግሞ የመምጣት ምክንያት (Motivation):</span>
                                    <p className="text-foreground bg-muted/20 p-2 rounded-lg italic">{selectedItem.q20_visit_motivation || 'ምንም አልተጻፈም'}</p>
                                </div>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
