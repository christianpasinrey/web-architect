export interface FieldType {
    id: number;
    label: string;
    column_type: string;
    created_at: string;
    updated_at: string;
}

export interface Field {
    id: number;
    db_model_id: number;
    field_type_id: number;
    name: string;
    label: string;
    default: string | null;
    nullable: boolean;
    unique: boolean;
    index: boolean;
    primary: boolean;
    auto_increment: boolean;
    foreign: boolean;
    foreign_table: string | null;
    foreign_key: string | null;
    created_at: string;
    updated_at: string;
    field_type: FieldType;
}

export interface Model {
    id: number;
    name: string;
    table: string;
    fillable: string | null;
    guarded: string | null;
    with: string | null;
    hidden: string | null;
    appends: string | null;
    casts: string | null;
    relations: string | null;
    created_at: string;
    updated_at: string;
    fields: Field[];
}
