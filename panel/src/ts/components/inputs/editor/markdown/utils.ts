import { Mark, MarkType, Node } from "prosemirror-model";
import { EditorState } from "prosemirror-state";

export function getMarkRange(state: EditorState, markType: MarkType): { from: number; to: number; mark: Mark; node: Node } | null {
    const { $from } = state.selection;
    let from = $from.pos;
    let to = $from.pos;

    const parent = $from.parent;
    let found = false;
    let foundMark: Mark;

    parent.forEach((node, offset) => {
        if (!node.isText) {
            return;
        }
        node.marks.forEach((mark) => {
            if (mark.type === markType) {
                const nodeStart = $from.start() + offset;
                const nodeEnd = nodeStart + node.nodeSize;
                if (nodeStart <= $from.pos && nodeEnd >= $from.pos) {
                    from = nodeStart;
                    to = nodeEnd;
                    found = true;
                    foundMark = mark;
                }
            }
        });
    });

    return found
        ? {
              from: from,
              to: to,
              mark: foundMark!,
              node: state.doc.nodeAt(from)!,
          }
        : null;
}
