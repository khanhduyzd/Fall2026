
module com_and_or (
    input a, b, c, d,
    output e
);
    assign e = (a & b) | c | d;
endmodule

module seq_and_or (
    input a, b, c, d,
    input clk, rst,
    output reg f
);
    wire e;

    com_and_or u1 (
        .a(a),
        .b(b),
        .c(c),
        .d(d),
        .e(e)
    );

    always @(posedge clk or negedge rst) begin
        if (!rst)
            f <= 1'b0;
        else
            f <= e;
    end
endmodule
