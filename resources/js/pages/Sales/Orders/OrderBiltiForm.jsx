import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Badge, Col, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import ValidationErrors from '@/components/ValidationErrors';
import { FileUpload } from '@/components/File';
import BackButton from '@/components/button/back';
import { Icon } from '@iconify/react';
import NoData from '@/components/NoData.jsx';
import { DeleteAjax } from '@/components/Actions';

const OrderBiltiForm = () => {
    const {
        order,
        attachments: initialAttachments,
        directory,
        errors: serverSideError,
    } = usePage().props;

    const [processing, setProcessing] = useState(false);
    const [file, setFile] = useState(null);
    const [attachments, setAttachments] = useState(initialAttachments);
    const deleteFile = async (id) => {
        await axios.delete(route('file.delete', id));
        setAttachments((current) => current.filter((a) => a.id !== id));
        if (file && file.id === id) {
            setFile(null);
        }
    };
    const {
        handleSubmit,
        control,
        setError,
        formState: { errors },
    } = useForm();
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        const post_data = { ...data, file: file };
        setProcessing(true);
        Inertia.post(
            route('sales.order.bilti.create', {
                order: order['id'],
                file: file['id'],
            }),
            post_data,
            options,
        );
    };

    return (
        <>
            <Head title="Order Bilti Info" />
            <PageHeader
                title="Order Bilti Info"
                buttons={
                    <>
                        <BackButton href={route('sales.orders.index')} />
                    </>
                }
            />
            <PageContent>
                <Panel theme={'default'}>
                    <PanelHeader heading={'Attach Order Bilti'} />
                    <PanelBody>
                        <Row>
                            <ValidationErrors errors={serverSideError} />
                        </Row>

                        <Row>
                            <Col md={file ? 4 : 12}>
                                <h6 className="fw-bold mb-2">
                                    Upload New Bilti
                                </h6>
                                <FileUpload
                                    directory={directory}
                                    progress={setProcessing}
                                    updated={setFile}
                                />
                            </Col>

                            {file && (
                                <Col md={8}>
                                    <h6 className="fw-bold mb-2">Preview</h6>
                                    <Row>
                                        <Col md={'auto'}>
                                            <div
                                                className="card b-0 shadow-sm"
                                                style={{ maxWidth: 200 }}
                                                key={file.id}
                                            >
                                                <div className="overflow-hidden text-center bg-light">
                                                    <img
                                                        src={file.thumbnail}
                                                        className={'height-150'}
                                                        alt={file.name}
                                                    />
                                                </div>
                                                <div className="card-body p-2">
                                                    <h6
                                                        className="card-title text-truncate mb-2"
                                                        title={file.name}
                                                    >
                                                        {file.name}
                                                    </h6>
                                                    <div className="d-flex gap-1">
                                                        <a
                                                            className={
                                                                'btn btn-sm btn-outline-cyan flex-fill'
                                                            }
                                                            href={route(
                                                                'file.show',
                                                                file.id,
                                                            )}
                                                            target={'_blank'}
                                                            rel={
                                                                'noopener noreferrer'
                                                            }
                                                            title={
                                                                'Download file'
                                                            }
                                                        >
                                                            <Icon
                                                                icon={
                                                                    'solar:gallery-download-line-duotone'
                                                                }
                                                            />
                                                            Download
                                                        </a>
                                                        <DeleteAjax
                                                            className="fs-18px"
                                                            id={file.id}
                                                            onDelete={
                                                                deleteFile
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </Col>
                                        <Col>
                                            <div className="note alert-warning d-flex align-items-center gap-3 flex-wrap mb-3 pe-2">
                                                <div className="note-content flex-fill">
                                                    <p className="mb-0">
                                                        <Icon
                                                            icon={
                                                                'solar:danger-triangle-bold-duotone'
                                                            }
                                                            className={'me-1'}
                                                        />
                                                        This bilti voucher is
                                                        not saved yet. Save it
                                                        to keep it attached to
                                                        this order.
                                                    </p>
                                                </div>
                                            </div>
                                            <LoadingButton
                                                className={'w-100'}
                                                processing={processing}
                                                onClick={handleSubmit(
                                                    sendRequest,
                                                )}
                                            >
                                                Save Bilti
                                            </LoadingButton>
                                        </Col>
                                    </Row>
                                </Col>
                            )}
                        </Row>

                        <hr className="my-4" />

                        <h6 className="fw-bold mb-3">
                            Attached Files{' '}
                            <Badge pill bg={'secondary'}>
                                {attachments.length}
                            </Badge>
                        </h6>
                        <Row className={'g-3'}>
                            {attachments.map((attachment) => (
                                <Col
                                    xs={12}
                                    sm={6}
                                    md={'auto'}
                                    key={attachment.id}
                                >
                                    <div className="card b-0 shadow-sm h-100">
                                        <div className="border-bottom overflow-hidden text-center rounded-top bg-light">
                                            {attachment.is_image && (
                                                <img
                                                    src={attachment.thumbnail}
                                                    className={'height-150'}
                                                    alt={attachment.name}
                                                />
                                            )}
                                            {!attachment.is_image && (
                                                <div className="text-center fs-80px text-muted py-3">
                                                    <Icon
                                                        icon={
                                                            'ph:file-pdf-duotone'
                                                        }
                                                    />
                                                </div>
                                            )}
                                        </div>
                                        <div className="card-body p-2">
                                            <div
                                                className="card-title fw-bold text-truncate mb-2"
                                                title={attachment.name}
                                            >
                                                {attachment.name}
                                            </div>
                                            <div className="d-flex gap-1">
                                                <a
                                                    className={
                                                        'btn btn-sm btn-outline-cyan flex-fill'
                                                    }
                                                    href={route(
                                                        'file.show',
                                                        attachment.id,
                                                    )}
                                                    target={'_blank'}
                                                    rel={'noopener noreferrer'}
                                                    title={'Download file'}
                                                >
                                                    <Icon
                                                        icon={
                                                            'solar:gallery-download-line-duotone'
                                                        }
                                                    />
                                                    Download
                                                </a>
                                                <DeleteAjax
                                                    id={attachment.id}
                                                    onDelete={deleteFile}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </Col>
                            ))}
                        </Row>
                        {attachments.length === 0 && (
                            <NoData label="No bilti attachments found." />
                        )}
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default OrderBiltiForm;
