import * as React from 'react';
import { useEffect, useRef, useState } from 'react';
import { Button, Col, Form, Modal, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import StyledSelect from '@/components/StyledSelect';
import { notifyMessage, serverSideError, UNIT_BOX, UNIT_SUIT, UNIT_THAAN, usePackingUnits } from '@/util/util';
import { confirmSwal } from '@/util/swal';
import soAjax from '@/routes/ajax/so';

export const OrderItemForm = ({
    orderId,
    item,
    onClose,
    setItems,
    products,
    show,
    commission,
    loading,
    confirmation,
    onConfirm,
}) => {
    const packingUnits = usePackingUnits();
    const itemDetail = _.find(products, { product_id: item.product_id ?? 0 }) ?? null;
    const [product, setProduct] = useState(
        itemDetail
            ? {
                  ...itemDetail,
                  product: { ...itemDetail },
                  qty: item.qty ?? "",
                  unit: item.unit ?? "",
                  price: item.price ?? "",
                  size: item.size ?? "",
                  commission: item.commission ?? "",
              }
            : "",
    );

    const {
        register,
        handleSubmit,
        setValue,
        setFocus,
        watch,
        reset,
        control,
        formState: { errors },
    } = useForm({ defaultValues: product });

    const [processing, setProcessing] = useState(false);
    const { unit } = watch();
    const updateValue = (item) => {
        setProduct(item);
        const { is_box, unit_price, suit_price, name, finish, size, brand_id } = { ...item };
        setFocus("unit");
        setValue("unit", is_box ? UNIT_BOX : UNIT_THAAN, { shouldDirty: true });
        setValue("name", name ?? "", { shouldDirty: true });
        setValue("finish", finish ?? "", { shouldDirty: true });
        setValue("size", size ?? "", { shouldDirty: true });
        setValue("price", is_box ? (unit_price ?? "") : suit_price, { shouldDirty: true });
        setValue("commission", getCommission(brand_id) ?? "", { shouldDirty: true });
    };
    const didMount = React.useRef(false);
    useEffect(() => {
        if (unit && didMount.current) {
            if (unit === UNIT_SUIT) {
                setValue("price", product?.suit_price ?? "");
            }
            if (unit === UNIT_THAAN) {
                setValue("price", product?.unit_price ?? "");
            }
        } else {
            didMount.current = true;
        }
    }, [unit]);
    const productRef = useRef(null);

    const handleClose = () => {
        onClose();
    };
    const resetForm = (data) => {
        setProduct({});
        reset(
            {
                ...data,
                name: "",
                product: "",
                qty: "",
                size: "",
                price: "",
                unit: "",
            },
            {
                keepDefaultValues: true,
                keepDirtyValues: false,
            },
        );
        productRef.current?.focus();
    };
    const sendRequest = async (data) => {
        setProcessing(true);
        axios({
            method: "post",
            url: soAjax.item.add(orderId).url,
            data: {
                ...data,
                order_id: item.order_id,
                item_id: item.id,
                discount: item.discount,
            },
        })
            .then((res) => {
                // setWarning(false)
                setItems(res.data.items);

                if (item.id > 0) {
                    onClose();
                } else {
                    resetForm(data);
                }
                notifyMessage({ title: "Success", type: "success", message: "Items saved successfully" });
            })
            .finally((res) => {
                setProcessing(false);
            })
            .catch((error) => {
                setProcessing(false);

                const { status, data } = error.response;
                serverSideError(error);
                /*if (status === 422) {
                setWarning(true)
            } else {
                serverSideError(error)
            }*/
            });
        /*onSave({
            ...data,
            order_id: item.order_id,
            item_id: item.id,
            discount: item.discount,
            oversold: false
        })*/
    };

    const sendOverSoldRequest = async (data) => {
        onConfirm(false);
        onSave({
            ...data,
            order_id: item.order_id,
            item_id: item.id,
            discount: item.discount,
            oversold: true,
        });
    };

    useEffect(() => {
        if (!confirmation) {
            return;
        }
        confirmSwal({
            text: "you want to do the over sale!",
            confirmButtonText: "Yes, Please",
            confirmButtonStyle: "danger",
        }).then(({ isConfirmed }) => {
            if (isConfirmed) {
                handleSubmit(sendOverSoldRequest)();
            } else {
                onConfirm(false);
            }
        });
    }, [confirmation]);

    const getCommission = (brand_id) => {
        return _.find(commission, (id, index) => {
            return "brand_" + brand_id === index;
        });
    };
    const isLoading = () => {
        if (loading) return true;

        return false;
    };

    const units = packingUnits.filter((val) => {
        if (product?.is_box) return val === "Box";
        else return val !== "Box";
    });

    const showFormData = () => {
        resetForm({});
    };

    return (
        <>
            <Modal show={true} backdrop="static" size={"xl"} keyboard={true}>
                <Modal.Header>
                    <Modal.Title>Order Product</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
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
                                                isClearable
                                                ref={productRef}
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
                                        defaultValue={product?.name ?? ""}
                                        {...register("name", { required: true })}
                                        isInvalid={errors.name}
                                        readOnly={true}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Finish:</Form.Label>
                                    <Form.Control defaultValue={product?.finish ?? ""} readOnly={true} />
                                </Form.Group>
                            </Col>
                        </Row>

                        <Row>
                            <Col md={3}>
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

                            <Col md={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Qty:</Form.Label>
                                    <Form.Control
                                        {...register("qty", { required: true, min: 1 })}
                                        isInvalid={errors.qty}
                                        defaultValue={product?.qty ?? ""}
                                        placeholder={"Qty"}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={3}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Size:</Form.Label>
                                    <Form.Control
                                        {...register("size", { required: true, min: 1 })}
                                        isInvalid={errors.size}
                                        defaultValue={product?.size ?? ""}
                                        placeholder={"Size in meters"}
                                    />
                                </Form.Group>
                            </Col>
                            {commission && (
                                <Col md={2}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Commission Rate:</Form.Label>
                                        <Form.Control
                                            {...register("commission", { required: true, min: 0 })}
                                            isInvalid={errors.commission}
                                            defaultValue={product?.commission ?? ""}
                                            placeholder={"Commission"}
                                        />
                                    </Form.Group>
                                </Col>
                            )}

                            <Col md={2}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Price:</Form.Label>
                                    <Form.Control
                                        {...register("price", { required: true, min: 1 })}
                                        isInvalid={errors.price}
                                        defaultValue={product?.price ?? ""}
                                        placeholder={"Price"}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="warning" onClick={showFormData}>
                        Reset Form
                    </Button>
                    <Button variant="white" onClick={handleClose}>
                        Close
                    </Button>
                    <LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
                        Save
                    </LoadingButton>
                </Modal.Footer>
            </Modal>
        </>
    );
};
