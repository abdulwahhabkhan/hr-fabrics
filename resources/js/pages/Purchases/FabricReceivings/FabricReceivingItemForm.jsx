import * as React from 'react';
import { useState } from 'react';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { notifyMessage, serverSideError, UNIT_SUIT, usePackingUnits } from '@/util/util';

export const FabricReceivingItemForm = ({ item, orderId, onClose, setItems, products }) => {
    const packingUnits = usePackingUnits();
    const itemDetail = _.find(products, { product_id: item.product_id ?? 0 }) ?? null;
    const [product, setProduct] = useState(
        itemDetail
            ? {
                  ...itemDetail,
                  qty: item.qty ?? "",
                  voucher_no: item.voucher_no ?? "",
                  unit: item.unit ?? "Box",
                  size: item.size ?? "",
                  total_qty: item.total_qty ?? "",
              }
            : "",
    );
    const {
        control,
        register,
        handleSubmit,
        setValue,
        setFocus,
        watch,
        formState: { errors },
        reset,
    } = useForm({ defaultValues: product });
    const [processing, setProcessing] = useState(false);
    const updateValue = (item) => {
        setProduct(item);
        const { is_box, name, finish } = { ...item };

        setValue("unit", is_box ? "Box" : "Thaan", { shouldDirty: true });
        setValue("name", name ?? "", { shouldDirty: true });
        setValue("finish", finish ?? "", { shouldDirty: true });
        setFocus("qty");
    };
    const { unit } = watch();
    const handleClose = () => {
        onClose();
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        axios({
            method: "post",
            url: route("ajax.fabric-receiving.item", orderId),
            data: { ...data, order_id: item.order_id, item_id: item.id },
        })
            .then((res) => {
                setItems(res.data.items);

                notifyMessage({ title: "Success", type: "success", message: "Items saved successfully" });
                if (item.id > 0) {
                    onClose();
                } else {
                    resetForm(data);
                }
            })
            .finally((res) => {
                setProcessing(false);
            })
            .catch((error) => {
                serverSideError(error);
            });
    };

    const resetForm = (data) => {
        reset({
            ...data,
        });
    };

    const isSizeRequired = () => {
        return parseInt(product.is_box) === 1 || unit === UNIT_SUIT;
    };
    const units = packingUnits.filter((val) => {
        if (product.is_box) return val === "Box";
        else return val !== "Box";
    });
    return (
        <Modal show={true} backdrop="static" size={"lg"} keyboard={true}>
            <Modal.Header>
                <Modal.Title>Manage Fabric Receiving Product</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                    <Row>
                        <Col md={12}>
                            <Form.Group className="mb-3">
                                <Form.Label>Voucher No:</Form.Label>
                                <Form.Control
                                    defaultValue={product.voucher_no ?? ""}
                                    {...register("voucher_no", { required: true })}
                                    isInvalid={errors.voucher_no}
                                />
                            </Form.Group>
                        </Col>
                    </Row>
                    <Row>
                        <Col md={12}>
                            <Form.Group className="mb-3">
                                <Form.Label>Product SKU:</Form.Label>
                                <Controller
                                    render={({ field }) => (
                                        <StyledSelect
                                            {...field}
                                            options={products}
                                            onChange={(e) => {
                                                field.onChange(e);
                                                updateValue(e);
                                            }}
                                            defaultValue={product}
                                            getOptionValue={(option) => option["product_id"]}
                                            getOptionLabel={(option) => option["product_info"]}
                                        />
                                    )}
                                    control={control}
                                    name={"product"}
                                />
                            </Form.Group>
                        </Col>

                        <Col md={8}>
                            <Form.Group className="mb-3">
                                <Form.Label>Name:</Form.Label>
                                <Form.Control
                                    defaultValue={product.name ?? ""}
                                    {...register("name", { required: true })}
                                    isInvalid={errors.name}
                                    readOnly={true}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>Finish:</Form.Label>
                                <Form.Control {...register("finish", { required: true })} readOnly={true} />
                            </Form.Group>
                        </Col>
                    </Row>

                    <Row>
                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>Unit:</Form.Label>
                                <Form.Select
                                    {...register("unit", { required: true })}
                                 
                                >
                                    {units &&
                                        units.map((item, index) => {
                                            return <option key={index}>{item}</option>;
                                        })}
                                </Form.Select>
                            </Form.Group>
                        </Col>
                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>Qty:</Form.Label>
                                <Form.Control
                                    {...register("qty", { required: true, min: 1 })}
                                    isInvalid={errors.qty}
                                    defaultValue={product.qty ?? ""}
                                    placeholder={"Qty"}
                                />
                            </Form.Group>
                        </Col>
                        {isSizeRequired() && (
                            <Col md={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Size:</Form.Label>
                                    <Form.Control
                                        {...register("size", { required: true })}
                                        isInvalid={errors.size}
                                        placeholder={"size"}
                                    />
                                </Form.Group>
                            </Col>
                        )}

                        {!isSizeRequired() && (
                            <Col md={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Total Meters:</Form.Label>
                                    <Form.Control
                                        {...register("total_qty", { required: true })}
                                        isInvalid={errors.total_qty}
                                        placeholder={"total meters"}
                                    />
                                </Form.Group>
                            </Col>
                        )}
                    </Row>
                </form>
            </Modal.Body>
            <Modal.Footer>
                <LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
                    Save
                </LoadingButton>
                <Button variant="white" onClick={handleClose}>
                    Close
                </Button>
            </Modal.Footer>
        </Modal>
    );
};
