import type { Attribute, AttributeType } from '../../api/types';

/** Keeps inactive options only when the row already selected them. */
export function selectableAttributes(attributes: Attribute[], type: string, selectedIds: string[]): Attribute[] {
    return attributes.filter(
        (attribute) => attribute.type === type && (attribute.is_active || selectedIds.includes(attribute.id))
    );
}

const byOrderThenName = (a: AttributeType, b: AttributeType) =>
    a.sort_order - b.sort_order || a.name.localeCompare(b.name);

/** Active types in configured order. */
export function activeAttributeTypes(types: AttributeType[]): AttributeType[] {
    return types.filter((type) => type.is_active).sort(byOrderThenName);
}

/** Active types plus types holding a selected attribute, when the caller allows inactive selected types. */
export function typesForSelection(
    types: AttributeType[],
    attributes: Attribute[],
    selectedIds: string[],
    includeInactiveSelected: boolean
): AttributeType[] {
    const selectedTypes = new Set(
        attributes.filter((attribute) => selectedIds.includes(attribute.id)).map((attribute) => attribute.type)
    );

    return types
        .filter((type) => type.is_active || (includeInactiveSelected && selectedTypes.has(type.slug)))
        .sort(byOrderThenName);
}

/** First selected attribute ID of the type, or an empty string. */
export function selectedAttributeId(attributes: Attribute[], selectedIds: string[], type: string): string {
    return selectedIds.find(
        (id) => attributes.find((attribute) => attribute.id === id)?.type === type
    ) ?? '';
}

/** Replaces the selection of one type, appending the new value and keeping other types. */
export function setSelectedAttribute(
    attributes: Attribute[],
    selectedIds: string[],
    type: string,
    value: string
): string[] {
    const retained = selectedIds.filter(
        (id) => attributes.find((attribute) => attribute.id === id)?.type !== type
    );

    return value ? [...retained, value] : retained;
}
