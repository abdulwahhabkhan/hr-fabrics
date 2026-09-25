import React, { useEffect, useState } from 'react';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import { useForm } from 'react-hook-form';
import { Inertia } from '@/util/Inertia';
import axios from 'axios';
import { AttachFiles } from '@/components/File.jsx';
import journals from '@/routes/accounts/journals';
import attachmentRoutes from '@/routes/accounts/journals/attachment';

export default ({ id, show, callback }) => {
    const title = 'Attachment';
    const defaultValues = { name: null, description: null };
    const {
        register,
        handleSubmit,
        setValue,
        formState: { errors },
    } = useForm({ defaultValues: defaultValues });
    const [loading, setLoading] = useState(false);
    const [journal, setJournal] = useState({});
    const [files, setFiles] = useState([]);
    const [fileMeta, setFileMeta] = useState({
        morph_class: '',
        directory: '',
    });
    const [processing, setProcessing] = useState(false);
    useEffect(() => {
        if (id) {
            setLoading(true);
            axios
                .get(journals.attachment(id).url)
                .then((res) => {
                    const {
                        data: { journal, files, directory, morph_class },
                    } = res;
                    setFiles(files);
                    setLoading(false);
                    setJournal(journal);
                    setFileMeta({ ...fileMeta, morph_class, directory });
                });
        }
    }, [id]);
    const appendFile = (file) => {
        setFiles(files.concat(file));
    };
    const handleClose = () => {
        callback();
    };
    const options = {
        onFinish: () => {
            setProcessing(false);
        },
    };
    const sendRequest = async (data) => {
        const post_data = { ...data, file: file };
        setProcessing(true);
        Inertia.post(
            attachmentRoutes.store(id),
            post_data,
            options,
        );
    };
    return (
        <>
            <Modal show={show} backdrop="static" size={'lg'} keyboard={true}>
                <Modal.Header>
                    <Modal.Title>{title}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <form
                        action=""
                        className=""
                        onSubmit={handleSubmit(sendRequest)}
                    >
                        <Row>
                            <Col md={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Type:</Form.Label> &nbsp;
                                    <Form.Label>{journal.head}</Form.Label>
                                </Form.Group>
                            </Col>
                            <Col md={8}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Voucher No:</Form.Label>&nbsp;
                                    <Form.Label>
                                        {journal.reference_no}
                                    </Form.Label>
                                </Form.Group>
                            </Col>
                        </Row>
                        <Form.Group className="mb-3">
                            <Form.Label>Detail:</Form.Label>&nbsp;
                            <Form.Label>{journal.detail}</Form.Label>
                        </Form.Group>
                    </form>
                    <AttachFiles
                        directory={fileMeta.directory}
                        morph_class={fileMeta.morph_class}
                        morph_id={journal.id}
                        progress={setProcessing}
                        files={files}
                        updateFile={appendFile}
                    />
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="white" onClick={handleClose}>
                        Close
                    </Button>
                </Modal.Footer>
            </Modal>
        </>
    );
};
