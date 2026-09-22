import React from "react";
import Select from "react-select";

const selectStyles = {
    control: (base, state) => ({
        ...base,
        minHeight: "calc(1.5em + 0.75rem + 2px)",
        backgroundColor: "var(--bs-component-bg)",
        borderColor: state.isFocused ? "var(--bs-component-focus-border-color)" : "var(--bs-component-border-color)",
        borderRadius: "var(--bs-border-radius)",
        boxShadow: state.isFocused ? "0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25)" : "none",
        "&:hover": {
            borderColor: state.isFocused ? "var(--bs-component-focus-border-color)" : "var(--bs-component-border-color)",
        },
    }),
    valueContainer: (base) => ({
        ...base,
        color: "var(--bs-component-color)",
    }),
    input: (base) => ({
        ...base,
        color: "var(--bs-component-color)",
    }),
    singleValue: (base) => ({
        ...base,
        color: "var(--bs-component-color)",
    }),
    placeholder: (base) => ({
        ...base,
        color: "var(--bs-secondary-color)",
    }),
    menu: (base) => ({
        ...base,
        backgroundColor: "var(--bs-component-dropdown-bg)",
        border: "1px solid var(--bs-component-dropdown-border-color)",
        borderRadius: "var(--bs-border-radius)",
        zIndex: 5,
    }),
    menuList: (base) => ({
        ...base,
        backgroundColor: "var(--bs-component-dropdown-bg)",
    }),
    option: (base, state) => ({
        ...base,
        backgroundColor: state.isSelected
            ? "var(--bs-component-active-bg)"
            : state.isFocused
                ? "var(--bs-component-dropdown-hover-bg)"
                : "transparent",
        color: state.isSelected ? "var(--bs-component-active-color)" : "var(--bs-component-color)",
        cursor: "pointer",
    }),
    indicatorSeparator: (base) => ({
        ...base,
        backgroundColor: "var(--bs-component-border-color)",
    }),
    dropdownIndicator: (base) => ({
        ...base,
        color: "var(--bs-component-color)",
    }),
    clearIndicator: (base) => ({
        ...base,
        color: "var(--bs-component-color)",
    }),
    multiValue: (base) => ({
        ...base,
        backgroundColor: "var(--bs-component-hover-bg)",
    }),
    multiValueLabel: (base) => ({
        ...base,
        color: "var(--bs-component-color)",
    }),
    singleValueDisabled: (base) => ({
        ...base,
        color: "var(--bs-component-disabled-color)",
    }),
};

const StyledSelect = (props) => <Select styles={selectStyles} {...props} />;

export default StyledSelect;
export { selectStyles };
