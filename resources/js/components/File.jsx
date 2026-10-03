import React, { useEffect, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { Icon } from '@iconify/react';
import { Button, Col, Image, ProgressBar, Row, Tooltip } from 'react-bootstrap';
import OverlayTrigger from '@/components/ui/OverlayTrigger';
import Moment from '@/components/Moment';
import { DeleteAjax } from '@/components/Actions.jsx';
import { deleteMethod, show, upload, view } from '@/routes/file';
import { Panel, PanelBody } from '@/components/panel/panel';

const thumbsContainer = {
    display: 'flex',
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: 16,
};

const thumb = {
    display: 'inline-flex',
    borderRadius: 2,
    border: '1px solid #eaeaea',
    marginBottom: 8,
    marginRight: 8,
    width: 100,
    height: 100,
    padding: 4,
    boxSizing: 'border-box',
};

const thumbInner = {
    display: 'flex',
    minWidth: 0,
    overflow: 'hidden',
};

const img = {
    display: 'block',
    width: 'auto',
    height: '100%',
};

export const FileUpload = ({
    directory,
    directoryPath,
    mimes,
    msg,
    updated,
    progress,
}) => {
    const acceptedMines = mimes ?? {
        'image/*': [],
        'application/pdf': [],
    };
    const supportedTypes = msg ?? 'Images Only';
    const [processing, setProcessing] = useState(false);
    const { getRootProps, getInputProps } = useDropzone({
        accept: acceptedMines,
        maxFiles: 1,
        onDrop: (acceptedFiles) => {
            acceptedFiles.map((file) => {
                progress(true);
                setProcessing(true);
                const formData = new FormData();
                formData.append('file', file);
                formData.append('directory', directory);
                //formData.append('directory_path', directoryPath ?? directory);
                return axios({
                    method: 'post',
                    data: formData,
                    url: upload().url,
                })
                    .then((res) => {
                        updated(res.data);
                    })
                    .finally((res) => {
                        setProcessing(false);
                        progress(false);
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            });
        },
    });
    const [files, setFiles] = useState([]);
    const thumbs = files.map((file) => (
        <div style={thumb} key={file.name}>
            <div style={thumbInner}>
                <img src={file.preview} style={img} />
            </div>
        </div>
    ));

    useEffect(
        () => () => {
            // Make sure to revoke the data uris to avoid memory leaks
            files.forEach((file) => URL.revokeObjectURL(file.preview));
        },
        [files],
    );

    return (
        <section>
            {processing !== false && <ProgressBar animated now={100} />}

            <div {...getRootProps({ className: 'dropzone' })}>
                <input {...getInputProps()} />

                <div className="dz-message needsclick">
                    Drop files <b>here</b> or <b>click</b> to upload.
                    <br />
                    {supportedTypes && (
                        <span className="dz-note needsclick">
                            {supportedTypes}
                        </span>
                    )}
                </div>
            </div>
            <aside style={thumbsContainer}>{thumbs}</aside>
        </section>
    );
};

export const ViewFile = ({ file }) => {
    const { file_name, file_path, download_url } = file;

    return (
        <>
            {download_url && (
                <>
                    <a href={download_url} title={file_name} target={'_blank'}>
                        <Image
                            fluid
                            className={'file-preview'}
                            src={download_url}
                            alt={file_name}
                        />
                    </a>
                </>
            )}
            {!download_url && (
                <a
                    href={view({ query: { path: file_path } }).url}
                    title={file_name}
                    target={'_blank'}
                >
                    <Image
                        fluid
                        className={'file-preview'}
                        src={view({ query: { path: file_path } }).url}
                        alt={file_name}
                    />
                </a>
            )}
        </>
    );
};

export const FileIcon = ({ file, size }) => {
    let iconSize = size ?? '';
    if (!file) {
        return (
            <OverlayTrigger
                placement={'bottom'}
                overlay={<Tooltip>No Attachment</Tooltip>}
            >
                <Icon
                    className={'thumb-icon'}
                    icon={'solar:gallery-bold-duotone'}
                />
            </OverlayTrigger>
        );
    }
    const { file_name, file_path, file_thumbnail } = file;
    if (file_path && file_name) {
        return (
            <>
                <a
                    href={view({ query: { path: file_path } }).url}
                    target={'_blank'}
                    className={'height-150 img-thumb'}
                >
                    {file_thumbnail && (
                        <OverlayTrigger
                            placement={'bottom'}
                            overlay={<Tooltip>View {file_name}</Tooltip>}
                        >
                            <img
                                src={file_thumbnail}
                                alt={file_name}
                                className={'img-rounded height-50'}
                            />
                        </OverlayTrigger>
                    )}
                    {!file_thumbnail && (
                        <OverlayTrigger
                            placement={'bottom'}
                            overlay={<Tooltip>No Attachment</Tooltip>}
                        >
                            <Icon icon={'solar:paperclip-bold-duotone'} />
                        </OverlayTrigger>
                    )}
                </a>
            </>
        );
    } else
        return (
            <>
                <Icon icon={'solar:gallery-bold-duotone'} />
            </>
        );
};
const deleteFile = async (id) => {
    await axios.delete(deleteMethod(id).url);
    /*setAttachments((current) => current.filter((a) => a.id !== id));
    if (file && file.id === id) {
        setFile(null);
    }*/
};
export const FileDetail = ({ file, fnDelete = undefined }) => {
    return (
        <>
            <Col xs={12} sm={6} md={'auto'} key={file.id}>
                <div className="card b-0 shadow-sm h-100">
                    <div className="border-bottom overflow-hidden text-center rounded-top bg-light">
                        {file.is_image !== undefined && file.is_image && (
                            <img
                                src={file.thumbnail}
                                className={'height-50'}
                                alt={file.name}
                            />
                        )}
                        {!file.is_image && (
                            <div className="text-center fs-80px text-muted py-3">
                                <Icon icon={'ph:file-pdf-duotone'} />
                            </div>
                        )}
                    </div>
                    <div className="card-body p-2">
                        <div
                            className="card-title fw-bold text-truncate mb-2"
                            title={file.name}
                        >
                            {file.name}
                        </div>
                        <div className="d-flex gap-1">
                            <a
                                className={
                                    'btn btn-sm btn-outline-cyan flex-fill'
                                }
                                href={show(file.id).url}
                                target={'_blank'}
                                rel={'noopener noreferrer'}
                                title={'Download file'}
                            >
                                <Icon
                                    icon={'solar:gallery-download-line-duotone'}
                                />
                                Download
                            </a>
                            {fnDelete !== undefined && (
                                <DeleteAjax id={file.id} onDelete={fnDelete} />
                            )}
                        </div>
                    </div>
                </div>
            </Col>
        </>
    );
};
/**
 * Human readable file size, e.g. 343568 -> "335.5 KB".
 */
const formatFileSize = (bytes) => {
    const size = Number(bytes);
    if (!size) {
        return null;
    }
    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(
        Math.floor(Math.log(size) / Math.log(1024)),
        units.length - 1,
    );
    const value = size / 1024 ** exponent;

    return `${exponent === 0 ? value : value.toFixed(1)} ${units[exponent]}`;
};

const FileDetails = ({ file }) => {
    return (
        <>
            <div className="file-preview">
                <a href={show(file.id).url} target={'_blank'}>
                    <img
                        src={file.thumbnail}
                        alt={file.name}
                        className={'rounded height-50'}
                    />
                </a>
            </div>
            <div className="file-content">
                <div className="file-content-main">
                    <div className="file-name">
                        <a href={show(file.id).url} target={'_blank'}>
                            {file.name}
                        </a>
                    </div>
                    <div className="file-details">
                        <div className="file-details-meta">
                            by&nbsp;
                            <span className={'file-author'}>
                                {file.created_by}
                            </span>
                            ,&nbsp;
                            <span
                                className={'file-time'}
                                title={file.created_at}
                            >
                                <Moment date={file.created_at} />
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

/**
 * Gallery tile for read-only pages: large preview of the file content
 * (image thumbnail or first page of a PDF), opening the file inline on click.
 */
const AttachmentTile = ({ file, fnDelete = undefined }) => {
    const viewUrl = file.preview || show(file.id).url;
    const fileSize = formatFileSize(file.size);

    return (
        <div className="hf-attachment">
            <a
                className="hf-attachment__media"
                href={viewUrl}
                target={'_blank'}
                rel={'noopener noreferrer'}
                title={`Open ${file.name}`}
            >
                {file.is_image ? (
                    <img src={file.thumbnail} alt={file.name} loading="lazy" />
                ) : (
                    <>
                        <Icon
                            className="hf-attachment__fallback"
                            icon={'ph:file-pdf-duotone'}
                        />
                        {file.preview && (
                            <iframe
                                src={`${file.preview}#toolbar=0&navpanes=0&scrollbar=0&view=FitH`}
                                title={file.name}
                                loading="lazy"
                                tabIndex={-1}
                            />
                        )}
                    </>
                )}
                <span className="hf-attachment__open">
                    <Icon icon={'solar:eye-bold-duotone'} /> Open
                </span>
            </a>
            <div className="hf-attachment__footer">
                <div className="hf-file__body">
                    <a
                        className="hf-file__name"
                        href={viewUrl}
                        target={'_blank'}
                        rel={'noopener noreferrer'}
                        title={file.name}
                    >
                        {file.name}
                    </a>
                    <div className="hf-file__meta">
                        {fileSize && <span>{fileSize}</span>}
                        <span>
                            <Moment date={file.created_at} />
                        </span>
                    </div>
                </div>
                <OverlayTrigger
                    placement={'top'}
                    overlay={<Tooltip>Download</Tooltip>}
                >
                    <a
                        className="hf-icon-btn hf-icon-btn--boxed"
                        href={show(file.id).url}
                        target={'_blank'}
                        rel={'noopener noreferrer'}
                        aria-label={`Download ${file.name}`}
                    >
                        <Icon
                            icon={'solar:download-minimalistic-bold-duotone'}
                        />
                    </a>
                </OverlayTrigger>
                {fnDelete !== undefined && (
                    <OverlayTrigger
                        placement={'top'}
                        overlay={<Tooltip>Delete</Tooltip>}
                    >
                        <span className="hf-icon-btn hf-icon-btn--boxed is-danger">
                            <DeleteAjax id={file.id} onDelete={fnDelete} />
                        </span>
                    </OverlayTrigger>
                )}
            </div>
        </div>
    );
};

export const PreviewAttachments = ({ attachments }) => {
    if (attachments.length === 0) {
        return null;
    }

    return (
        <Panel className="hf-order-card mt-3 mb-0 d-print-none">
            <PanelBody>
                <div className="hf-order-card__head">
                    <span className="hf-form-section__icon">
                        <Icon icon="solar:paperclip-bold-duotone" />
                    </span>
                    <div>
                        <h2 className="hf-form-section__title">Attachments</h2>
                        <p className="hf-form-section__desc">
                            {attachments.length}{' '}
                            {attachments.length === 1 ? 'file' : 'files'}
                        </p>
                    </div>
                </div>
                <div className="hf-attachments">
                    {attachments.map((attachment) => (
                        <AttachmentTile key={attachment.id} file={attachment} />
                    ))}
                </div>
            </PanelBody>
        </Panel>
    );
};

export const AttachFiles = ({
    directory,
    morph_class,
    morph_id,
    mimes,
    files,
    updateFile,
    progress,
}) => {
    const acceptedMines = mimes ?? 'image/*';
    const [processing, setProcessing] = useState(false);
    const { getRootProps, getInputProps, open, isDragActive } = useDropzone({
        accept: acceptedMines,
        maxFiles: 1,
        noClick: true,
        noKeyboard: true,
        onDrop: (acceptedFiles) => {
            acceptedFiles.map((file) => {
                progress(true);
                setProcessing(true);
                const formData = new FormData();
                formData.append('file', file);
                formData.append('directory', directory);
                formData.append('morph_class', morph_class);
                formData.append('morph_id', morph_id);
                return axios({
                    method: 'post',
                    data: formData,
                    url: upload().url,
                })
                    .then((res) => {
                        updateFile(res.data);
                    })
                    .finally((res) => {
                        setProcessing(false);
                        progress(false);
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            });
        },
    });
    const [attachments, setAttachments] = useState(files);

    useEffect(() => {
        setAttachments(files);
    }, [files]);

    const deleteAttachment = async (id) => {
        await axios.delete(deleteMethod(id).url);
        setAttachments((current) => current.filter((a) => a.id !== id));
    };

    return (
        <section
            {...getRootProps({
                className: `hf-files${isDragActive ? ' is-drag-active' : ''}`,
            })}
        >
            <input {...getInputProps()} />
            <div className="hf-files__toolbar">
                <span className="hf-files__count">
                    {attachments.length}{' '}
                    {attachments.length === 1 ? 'file' : 'files'}
                </span>
                <Button type={'button'} size={'xs'} onClick={open}>
                    <Icon icon={'solar:paperclip-bold-duotone'} />
                    &nbsp; Attach file
                </Button>
            </div>
            {processing !== false && (
                <ProgressBar animated now={100} className="mb-2" />
            )}
            {attachments.length === 0 ? (
                <button
                    type={'button'}
                    className="hf-files__empty"
                    onClick={open}
                >
                    <Icon icon={'solar:upload-minimalistic-bold-duotone'} />
                    <span>
                        Drop a file here or <b>browse</b>
                    </span>
                </button>
            ) : (
                <div className="hf-attachments">
                    {attachments.map((file) => (
                        <AttachmentTile
                            key={file.id}
                            file={file}
                            fnDelete={deleteAttachment}
                        />
                    ))}
                </div>
            )}
        </section>
    );
};

export const AttachFile = ({
    directory,
    mimes,
    file,
    updateFile,
    progress,
}) => {
    const acceptedMines = mimes ?? 'image/*';
    const [processing, setProcessing] = useState(false);
    const { getRootProps, getInputProps, open } = useDropzone({
        accept: acceptedMines,
        maxFiles: 1,
        onDrop: (acceptedFiles) => {
            acceptedFiles.map((file) => {
                progress(true);
                setProcessing(true);
                const formData = new FormData();
                formData.append('file', file);
                formData.append('directory', directory);
                return axios({
                    method: 'post',
                    data: formData,
                    url: upload().url,
                })
                    .then((res) => {
                        updateFile(res.data);
                    })
                    .finally((res) => {
                        setProcessing(false);
                        progress(false);
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            });
        },
    });

    return (
        <section>
            <Row>
                <Col sm={12}>
                    <h3 className={'font-normal'}>
                        Files
                        <div className={'float-end'}>
                            <Button type={'button'} size={'xs'} onClick={open}>
                                <Icon icon={'solar:paperclip-bold-duotone'} />
                                &nbsp; Attach file
                            </Button>
                        </div>
                    </h3>
                    {processing !== false && <ProgressBar animated now={100} />}
                    {!file && <p>No files are attached</p>}
                    <Row>
                        {file && (
                            <Col className={'file-row'} lg={12}>
                                <FileDetails file={file} />
                            </Col>
                        )}
                    </Row>
                </Col>
                <div {...getRootProps()}>
                    <input {...getInputProps()} />
                </div>
            </Row>
        </section>
    );
};
