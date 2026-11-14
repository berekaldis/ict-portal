import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Coffee, Download, ExternalLink, Printer, QrCode, Sparkles } from 'lucide-react';
import React, { useMemo, useState } from 'react';

interface Props {
    branches: string[];
    appUrl: string;
}

export default function SurveyQrGenerator({ branches, appUrl }: Props) {
    const [selectedBranch, setSelectedBranch] = useState(branches[0] || 'Bole Medhanialem');
    const [tableNumber, setTableNumber] = useState('');
    const [customBaseUrl, setCustomBaseUrl] = useState('');

    const targetUrl = useMemo(() => {
        const base = customBaseUrl.trim() || (typeof window !== 'undefined' ? window.location.origin : appUrl);
        const params = new URLSearchParams();
        if (selectedBranch) params.set('branch', selectedBranch);
        if (tableNumber.trim()) params.set('table', tableNumber.trim());
        return `${base}/survey?${params.toString()}`;
    }, [selectedBranch, tableNumber, customBaseUrl, appUrl]);

    const qrImageUrl = useMemo(() => {
        return `https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=${encodeURIComponent(targetUrl)}&margin=10`;
    }, [targetUrl]);

    const handlePrint = () => {
        window.print();
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Customer Survey', href: '/surveys' }, { title: 'Branch QR Generator' }]}>
            <Head title="Branch QR Generator | Kaldis Coffee" />

            <div className="space-y-6 max-w-5xl mx-auto pb-12">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border/40">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="outline" size="icon" className="h-9 w-9">
                            <Link href={route('surveys.index')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                <QrCode className="h-5 w-5 text-amber-600" /> Branch QR Code & Table Tent Generator
                            </h1>
                            <p className="text-xs text-muted-foreground">
                                Generate customized QR codes for Kaldis branch tables and print branded customer survey cards
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button onClick={handlePrint} size="sm" className="h-9 bg-amber-700 hover:bg-amber-800 text-white">
                            <Printer className="w-4 h-4 mr-1.5" /> Print Table Tent Card
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    {/* Controls Panel */}
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-3.5 px-5 border-b border-border/40">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Coffee className="w-4 h-4 text-amber-600" /> QR Target Parameters
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Configure the branch location and table number encoded into the QR code
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5 space-y-4">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Select Kaldis Branch</Label>
                                <select
                                    value={selectedBranch}
                                    onChange={(e) => setSelectedBranch(e.target.value)}
                                    className="w-full text-xs rounded-lg border-border/70 bg-muted/20 py-2.5 px-3 font-medium"
                                >
                                    {branches.map((b) => (
                                        <option key={b} value={b}>{b}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Table Number (Optional)</Label>
                                <Input
                                    placeholder="e.g. 04, VIP 1, Patio 02"
                                    value={tableNumber}
                                    onChange={(e) => setTableNumber(e.target.value)}
                                    className="text-xs h-9"
                                />
                                <p className="text-[11px] text-muted-foreground">
                                    Leave blank for general branch-wide counter or entrance stand cards.
                                </p>
                            </div>

                            <div className="space-y-1.5 pt-2 border-t border-border/40">
                                <Label className="text-xs font-semibold">Target URL Encoded in QR</Label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        value={targetUrl}
                                        readOnly
                                        className="text-xs h-8 font-mono bg-muted/30"
                                    />
                                    <Button asChild variant="outline" size="sm" className="h-8 shrink-0">
                                        <a href={targetUrl} target="_blank" rel="noreferrer">
                                            <ExternalLink className="w-3.5 h-3.5" />
                                        </a>
                                    </Button>
                                </div>
                            </div>

                            <div className="pt-2 flex gap-2">
                                <Button asChild variant="outline" size="sm" className="w-full h-8 text-xs">
                                    <a href={qrImageUrl} download={`kaldis_qr_${selectedBranch}.png`}>
                                        <Download className="w-3.5 h-3.5 mr-1" /> Download High-Res PNG
                                    </a>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Printable Table Tent Card Preview */}
                    <div className="print-area">
                        <div className="bg-white border-2 border-amber-600/30 rounded-3xl p-6 sm:p-8 text-center shadow-lg space-y-4 max-w-sm mx-auto">
                            {/* Logo & Header */}
                            <div className="space-y-1.5">
                                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-500/10 text-amber-700">
                                    <Coffee className="w-6 h-6" />
                                </div>
                                <h3 className="font-extrabold text-stone-900 tracking-tight text-lg">
                                    KALDI'S COFFEE
                                </h3>
                                <p className="text-[11px] font-bold text-amber-800 tracking-wider uppercase">
                                    የደንበኞች አስተያየት መጠይቅ
                                </p>
                            </div>

                            {/* Branch Badge */}
                            <div className="inline-flex items-center gap-1.5 bg-stone-100 border border-stone-200 px-3 py-1 rounded-full text-xs font-semibold text-stone-800">
                                <span>📍 {selectedBranch}</span>
                                {tableNumber && <span className="text-amber-700">• ጠረጴዛ {tableNumber}</span>}
                            </div>

                            {/* QR Code Container */}
                            <div className="p-3 bg-white rounded-2xl border border-stone-200 inline-block shadow-xs">
                                <img
                                    src={qrImageUrl}
                                    alt="Kaldis Survey QR Code"
                                    className="w-48 h-48 mx-auto rounded-lg object-contain"
                                />
                            </div>

                            {/* Instructions */}
                            <div className="space-y-1 text-xs text-stone-600">
                                <p className="font-bold text-stone-800">
                                    ስልክዎን በማንሳት የQR ኮዱን ይቃኙ
                                </p>
                                <p className="text-[11px] text-stone-500">
                                    Scan QR with your phone camera to share your valued feedback
                                </p>
                            </div>

                            <div className="pt-2 border-t border-stone-100 text-[10px] text-stone-400">
                                እናመሰግናለን! • Thank you for dining with Kaldi's Coffee!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
