import { expect, it } from 'vitest';

import type { Attribute, AttributeType } from '../../api/types';
import {
    activeAttributeTypes,
    selectableAttributes,
    selectedAttributeId,
    setSelectedAttribute,
    typesForSelection,
} from './attributeOptions';

const types: AttributeType[] = [
    { id: 't2', name: 'Display', slug: 'display', sort_order: 3, is_active: true },
    { id: 't1', name: 'Finishing', slug: 'finishing', sort_order: 1, is_active: true },
    { id: 't4', name: 'Retired', slug: 'retired', sort_order: 4, is_active: false },
];

const base = { price: '0.00', cost: '0.00', type_name: 'Finishing', branch_id: null, branch: null };
const attributes: Attribute[] = [
    { ...base, id: 'a1', name: 'Glossy', type: 'finishing', is_active: true },
    { ...base, id: 'a2', name: 'Matte', type: 'finishing', is_active: false },
    { ...base, id: 'a3', name: 'X-Banner', type: 'display', is_active: true },
    { ...base, id: 'a4', name: 'Old Display', type: 'display', is_active: false },
    { ...base, id: 'a5', name: 'Potong Lurus', type: 'cut', is_active: true },
    { ...base, id: 'a6', name: 'Retired Option', type: 'retired', is_active: false },
    { ...base, id: 'a7', name: 'Custom Finish', type: 'custom', is_active: true },
];

it('offers active options plus inactive options already selected for this row', () => {
    expect(selectableAttributes(attributes, 'finishing', []).map((a) => a.id)).toEqual(['a1']);
    expect(selectableAttributes(attributes, 'finishing', ['a2']).map((a) => a.id)).toEqual(['a1', 'a2']);
    expect(selectableAttributes(attributes, 'display', ['a1']).map((a) => a.id)).toEqual(['a3']);
});

it('keeps selectableAttributes type-agnostic for cut and custom types', () => {
    expect(selectableAttributes(attributes, 'cut', []).map((a) => a.id)).toEqual(['a5']);
    expect(selectableAttributes(attributes, 'custom', []).map((a) => a.id)).toEqual(['a7']);
    expect(selectableAttributes(attributes, 'retired', ['a6']).map((a) => a.id)).toEqual(['a6']);
});

it('lists active attribute types sorted by order then name', () => {
    expect(activeAttributeTypes(types).map((type) => type.slug)).toEqual(['finishing', 'display']);
    expect(activeAttributeTypes(types).map((type) => type.slug)).not.toContain('retired');
});

it('only lists active types when inactive selected types are excluded', () => {
    expect(typesForSelection(types, attributes, [], false).map((type) => type.slug)).toEqual([
        'finishing',
        'display',
    ]);
    expect(typesForSelection(types, attributes, ['a6'], false).map((type) => type.slug)).toEqual([
        'finishing',
        'display',
    ]);
});

it('adds an inactive type only when one of its attributes is selected', () => {
    expect(typesForSelection(types, attributes, [], true).map((type) => type.slug)).toEqual([
        'finishing',
        'display',
    ]);
    expect(typesForSelection(types, attributes, ['a1'], true).map((type) => type.slug)).toEqual([
        'finishing',
        'display',
    ]);
    expect(typesForSelection(types, attributes, ['a6'], true).map((type) => type.slug)).toEqual([
        'finishing',
        'display',
        'retired',
    ]);
});

it('finds the first selected attribute of a type', () => {
    expect(selectedAttributeId(attributes, ['a3', 'a1'], 'finishing')).toBe('a1');
    expect(selectedAttributeId(attributes, ['a3', 'a1'], 'display')).toBe('a3');
    expect(selectedAttributeId(attributes, ['a3'], 'cut')).toBe('');
});

it('replaces, removes, and preserves selections per attribute type', () => {
    expect(setSelectedAttribute(attributes, ['a1', 'a3'], 'finishing', 'a2')).toEqual(['a3', 'a2']);
    expect(setSelectedAttribute(attributes, ['a1', 'a3'], 'finishing', '')).toEqual(['a3']);
    expect(setSelectedAttribute(attributes, ['a3'], 'cut', 'a5')).toEqual(['a3', 'a5']);
});
