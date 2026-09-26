import React, { useState } from 'react';
import { Icon } from '@iconify/react';
import { Spinner } from 'react-bootstrap';

const PAGE_MARGIN_MM = 10;
const CM_TO_PT = 72 / 2.54;
const PAGE_MARGIN_LEFT = 2 * CM_TO_PT;
const PAGE_MARGIN_RIGHT = 1 * CM_TO_PT;

/**
 * Snapshots the referenced element and saves it as a multi-page A4 PDF.
 * The browser renders the snapshot, so Urdu text is shaped correctly.
 */
export default function DownloadPdf({
    target,
    fileName = 'document.pdf',
    className = '',
    ...props
}) {
    const [processing, setProcessing] = useState(false);

    const download = async () => {
        const element = target?.current;
        if (!element) {
            return;
        }
        setProcessing(true);
        element.classList.add('pdf-export');
        try {
            const [{ toPng }, { jsPDF }] = await Promise.all([
                import('html-to-image'),
                import('jspdf'),
            ]);
            const image = await toPng(element, {
                pixelRatio: 2,
                backgroundColor: '#ffffff',
            });

            const pdf = new jsPDF({
                unit: 'pt',
                format: 'a4',
                orientation: 'portrait',
            });
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const contentWidth = pageWidth - PAGE_MARGIN_LEFT - PAGE_MARGIN_RIGHT;
            const contentHeight = pageHeight - PAGE_MARGIN_MM * 2;
            const { width, height } = pdf.getImageProperties(image);
            const imageHeight = (height * contentWidth) / width;

            for (
                let offset = 0;
                offset < imageHeight;
                offset += contentHeight
            ) {
                if (offset > 0) {
                    pdf.addPage();
                }
                pdf.addImage(
                    image,
                    'PNG',
                    PAGE_MARGIN_LEFT,
                    PAGE_MARGIN_MM - offset,
                    contentWidth,
                    imageHeight,
                );
                // Mask the slices that spill into the top and bottom margins.
                pdf.setFillColor(255, 255, 255);
                pdf.rect(0, 0, pageWidth, PAGE_MARGIN_MM, 'F');
                pdf.rect(
                    0,
                    pageHeight - PAGE_MARGIN_MM,
                    pageWidth,
                    PAGE_MARGIN_MM,
                    'F',
                );
            }

            pdf.save(fileName);
        } catch (error) {
            console.error('PDF export failed', error);
        } finally {
            element.classList.remove('pdf-export');
            setProcessing(false);
        }
    };

    return (
        <button
            type="button"
            className={'btn btn-sm btn-white hidden-print ' + className}
            onClick={download}
            disabled={processing}
            {...props}
        >
            {processing ? (
                <Spinner
                    as="span"
                    animation="border"
                    size="sm"
                    role="status"
                    aria-hidden="true"
                />
            ) : (
                <Icon icon={'solar:file-download-bold-duotone'} />
            )}{' '}
            PDF
        </button>
    );
}
