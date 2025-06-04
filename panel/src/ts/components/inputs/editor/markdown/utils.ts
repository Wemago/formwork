import { Mark, MarkType } from "prosemirror-model";
import { EditorState } from "prosemirror-state";

export function getMarkRange(state: EditorState, markType: MarkType): { from: number; to: number; mark?: Mark } | null {
    const { $from } = state.selection;
    let start = $from.pos;
    let end = $from.pos;

    const parent = $from.parent;
    let found = false;
    let foundMark: Mark | undefined;

    parent.forEach((node, offset) => {
        if (!node.isText) {
            return;
        }
        node.marks.forEach((mark) => {
            if (mark.type === markType) {
                const nodeStart = $from.start() + offset;
                const nodeEnd = nodeStart + node.nodeSize;
                if (nodeStart <= $from.pos && nodeEnd >= $from.pos) {
                    start = nodeStart;
                    end = nodeEnd;
                    found = true;
                    foundMark = mark;
                }
            }
        });
    });

    return found ? { from: start, to: end, mark: foundMark } : null;
}
