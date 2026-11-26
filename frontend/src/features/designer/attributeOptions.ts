import type { Attribute } from '../../api/types';

/** Keeps inactive options only when the row already selected them. */
export function selectableAttributes(attributes: Attribute[], type: string, selectedIds: string[]): Attribute[] {
    return attributes.filter(
        (attribute) => attribute.type === type && (attribute.is_active || selectedIds.includes(attribute.id))
    );
}
