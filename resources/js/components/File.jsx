import React, { useEffect, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { Icon } from '@iconify/react';
import {
    Button,
    Col,
    Image,
    OverlayTrigger,
    ProgressBar,
    Row,
    Tooltip,
} from 'react-bootstrap';
import Moment, { MomentFull } from '@/components/Moment';
import { DeleteAjax } from '@/components/Actions.jsx';

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
                    url: route('file.upload'),
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
                    href={route('file.view', { path: file_path })}
                    title={file_name}
                    target={'_blank'}
                >
                    <Image
                        fluid
                        className={'file-preview'}
                        src={route('file.view', { path: file_path })}
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
                    href={route('file.view', { path: file_path })}
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
    await axios.delete(route('file.delete', id));
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
                                href={route('file.show', file.id)}
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
export const FileRow = ({ file, fnDelete = undefined }) => {
    return (
        <>
            <Col xs={12} sm={6} md={'auto'} key={file.id}>
                <div className="card b-0 shadow-sm flex-row">
                    <div className="border-bottom overflow-hidden text-center rounded-start">
                        {file.is_image !== undefined && file.is_image && (
                            <img
                                src={file.thumbnail}
                                className={'height-80'}
                                alt={file.name}
                            />
                        )}
                        {!file.is_image && (
                            <div className="text-center fs-80px text-muted py-3">
                                <Icon icon={'ph:file-pdf-duotone'} />
                            </div>
                        )}
                    </div>
                    <div className="card-body px-2 pt-2 pb-0">
                        <div
                            className="card-title fw-bold text-truncate mb-0"
                            title={file.name}
                        >
                            {file.name}
                        </div>
                        <div className="d-flex gap-2 justify-content-between">
                            <div className="card-text text-muted">
                                {file.size}
                            </div>

                            <div className="card-text text-muted">
                                <MomentFull date={file.created_at} />
                            </div>
                        </div>
                        <div className="btn-group btn-group-sm mt-1">
                            <a
                                className={'btn btn-white btn-xs'}
                                href={route('file.show', file.id)}
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
                                <DeleteAjax
                                    id={file.id}
                                    className={'btn-white p-1'}
                                    onDelete={fnDelete}
                                />
                            )}
                        </div>
                    </div>
                </div>
            </Col>
        </>
    );
};

const FileDetails = ({ file }) => {
    return (
        <>
            <div className="file-preview">
                <a href={route('file.show', file.id)} target={'_blank'}>
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
                        <a href={route('file.show', file.id)} target={'_blank'}>
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

export const PreviewAttachments = ({ attachments }) => {
    return (
        <>
            {attachments.length > 0 && (
                <div className="d-flex gap-4 mt-3 d-print-none">
                    {attachments.length > 0 &&
                        attachments.map((attachment) => {
                            return (
                                <div className="d-block" key={attachment.id}>
                                    <FileDetail file={attachment} />
                                </div>
                            );
                        })}
                </div>
            )}
        </>
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
                formData.append('morph_class', morph_class);
                formData.append('morph_id', morph_id);
                return axios({
                    method: 'post',
                    data: formData,
                    url: route('file.upload'),
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
        await axios.delete(route('file.delete', id));
        setAttachments((current) => current.filter((a) => a.id !== id));
    };

    return (
        <section>
            <Row>
                <Col sm={12}>
                    <h3 className={'font-normal'}>
                        Files
                        <div className={'float-end'}>
                            <Button type={'submit'} size={'xs'} onClick={open}>
                                <Icon icon={'solar:paperclip-bold-duotone'} />
                                &nbsp; Attach file
                            </Button>
                        </div>
                    </h3>
                    {processing !== false && <ProgressBar animated now={100} />}
                    {attachments.length === 0 && <p>No files are attached</p>}
                    <Row>
                        {attachments.length > 0 &&
                            attachments.map((file, index) => {
                                return (
                                    <FileRow
                                        key={index}
                                        file={file}
                                        fnDelete={deleteAttachment}
                                    />
                                );
                            })}
                    </Row>
                </Col>
                <div {...getRootProps()}>
                    <input {...getInputProps()} />
                </div>
            </Row>
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
                    url: route('file.upload'),
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
                            <Button type={'submit'} size={'xs'} onClick={open}>
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
