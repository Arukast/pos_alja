import type { Attribute, AttributeType } from '../../api/types';

/** Keeps inactive options only when the row already selected them. */
export function selectableAttributes(
    attributes: Attribute[],
    type: string,
    selectedIds: string[],
    allowedAttributeIds: string[] = []
): Attribute[] {
    return attributes.filter(
        (attribute) => attribute.type === type
            && (attribute.is_active || selectedIds.includes(attribute.id))
            && (allowedAttributeIds.length === 0
                || allowedAttributeIds.includes(attribute.id)
                || selectedIds.includes(attribute.id))
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
    includeInactiveSelected: boolean,
    allowedTypeIds: string[] = []
): AttributeType[] {
    const selectedTypes = new Set(
        attributes.filter((attribute) => selectedIds.includes(attribute.id)).map((attribute) => attribute.type)
    );

    return types
        .filter((type) => {
            const selected = includeInactiveSelected && selectedTypes.has(type.slug);

            return (type.is_active || selected)
                && (allowedTypeIds.length === 0 || allowedTypeIds.includes(type.id) || selected);
        })
        .sort(byOrderThenName);
}

/** Human-readable names for selected attribute IDs; unknown IDs are dropped so no UUID is displayed. */
export function selectedAttributeNames(attributes: Attribute[], selectedIds: string[]): string[] {
    return selectedIds.flatMap((id) => {
        const name = attributes.find((attribute) => attribute.id === id)?.name;

        return name === undefined ? [] : [name];
    });
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
