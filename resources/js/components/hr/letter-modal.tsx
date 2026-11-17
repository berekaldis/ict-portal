import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Coffee, Download, FileText, Printer, X } from 'lucide-react';
import React, { useRef } from 'react';

export interface LetterData {
    type: string;
    title: string;
    ref_no: string;
    date: string;
    to_branch: string;
    from_dept: string;
    name: string;
    gender: string;
    work_branch?: string;
    branch?: string;
    job?: string;
    requested_job?: string;
    assigned_job?: string;
    start_date?: string;
    probation_start?: string;
    manager_name?: string;
    manager_rank?: string;
    destination?: string;
    body: string;
}

interface Props {
    open: boolean;
    onClose: () => void;
    letter: LetterData | null;
}

export default function LetterModal({ open, onClose, letter }: Props) {
    if (!letter) return null;

    const handlePrint = () => {
        window.print();
    };

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto p-0 border-border/60">
                <div className="p-4 border-b border-border/40 flex items-center justify-between bg-muted/20">
                    <div className="flex items-center gap-2">
                        <FileText className="w-4 h-4 text-primary" />
                        <div>
                            <DialogTitle className="text-sm font-bold">{letter.title}</DialogTitle>
                            <DialogDescription className="text-xs">
                                Ref: <code className="font-mono">{letter.ref_no}</code> • {letter.type}
                            </DialogDescription>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button onClick={handlePrint} size="sm" className="h-8 text-xs">
                            <Printer className="w-3.5 h-3.5 mr-1" /> Print / Save PDF
                        </Button>
                    </div>
                </div>

                {/* Printable Letter Sheet */}
                <div className="p-8 sm:p-12 bg-white text-black font-sans leading-relaxed selection:bg-amber-100" id="printable-letter">
                    <style>{`
                        @media print {
                            body * {
                                visibility: hidden !important;
                            }
                            #printable-letter, #printable-letter * {
                                visibility: visible !important;
                            }
                            #printable-letter {
                                position: fixed !important;
                                left: 0 !important;
                                top: 0 !important;
                                width: 100% !important;
                                padding: 12mm 18mm !important;
                                margin: 0 !important;
                                background: white !important;
                                color: black !important;
                                box-shadow: none !important;
                            }
                        }
                    `}</style>
                    {/* Header */}
                    <div className="text-center space-y-1 pb-6 border-b-2 border-amber-900/40">
                        <div className="flex justify-center mb-2">
                            <img
                                src="/logokaldis.png"
                                alt="Kaldi's Coffee Logo"
                                className="h-16 w-16 sm:h-20 sm:w-20 object-contain drop-shadow-xs"
                                onError={(e) => {
                                    (e.target as HTMLImageElement).src = '/images/logo.png';
                                }}
                            />
                        </div>
                        <h2 className="text-xl font-extrabold tracking-wide text-amber-950 font-serif">
                            ካልዲስ ኮፊ ኃ/የተ/የግ/ማህበር
                        </h2>
                        <p className="text-xs text-stone-600 font-medium tracking-wider">
                            KALDI'S COFFEE PRIVATE LIMITED COMPANY • HR DEPARTMENT
                        </p>
                    </div>

                    {/* Metadata */}
                    <div className="pt-6 pb-4 flex justify-between text-xs text-stone-700 font-medium border-b border-stone-200">
                        <div>
                            <p><strong>ቁጥር (Ref No):</strong> <span className="font-mono">{letter.ref_no}</span></p>
                            <p className="mt-1"><strong>ለ (To):</strong> {letter.to_branch} ቅርንጫፍ</p>
                            <p className="mt-1"><strong>ከ (From):</strong> {letter.from_dept}</p>
                        </div>
                        <div className="text-right">
                            <p><strong>ቀን (Date):</strong> {letter.date}</p>
                        </div>
                    </div>

                    {/* Subject */}
                    <div className="py-6 text-center">
                        <h3 className="text-base font-bold text-stone-900 inline-block border-b-2 border-stone-800 pb-0.5">
                            ጉዳዩ፦ {letter.title}
                        </h3>
                    </div>

                    {/* Letter Body Text */}
                    <div className="text-sm leading-8 text-stone-800 text-justify indent-8 space-y-4 font-normal">
                        <p>{letter.body}</p>
                    </div>

                    {/* Signatures & Seal */}
                    <div className="pt-16 grid grid-cols-2 gap-8 text-xs text-stone-700">
                        <div className="space-y-1">
                            <p className="font-bold">ያዘጋጀው / ያረጋገጠው፡</p>
                            <div className="h-10 border-b border-stone-400 w-44"></div>
                            <p className="text-[11px] text-stone-500">የሰው ኃይል አስተዳደር ባለሙያ</p>
                        </div>

                        <div className="space-y-1 text-right">
                            <p className="font-bold">የቅርንጫፍ ሥራ አስኪያጅ ፊርማና ማህተም፡</p>
                            <div className="h-10 border-b border-stone-400 w-44 ml-auto"></div>
                            <p className="text-[11px] text-stone-500">ፊርማና ማህተም / Signature & Stamp</p>
                        </div>
                    </div>

                    <div className="mt-16 pt-4 border-t border-stone-200 text-center text-[10px] text-stone-400">
                        አዲስ አበባ፣ ኢትዮጵያ • ስልክ፡ +251 11 661 6161 • Kaldi's Coffee ICT & HR Portal
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
