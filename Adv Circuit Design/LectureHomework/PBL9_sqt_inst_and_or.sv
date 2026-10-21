
module com_and_or (
    input a, b, c, d,
    output e
);
    assign e = (a & b) | c | d;
endmodule


module seq_inst_and_or (
    input a, b, c, d, e, f, g,
    input clk, rst,
    output reg n
);

    wire h, i, l, m;
    reg j, k;

    // First AND-OR module
    com_and_or u1 (
        .a(a),
        .b(b),
        .c(c),
        .d(d),
        .e(h)
    );

    // Three-input AND gate
    assign i = a & c & d;

    // First register
    always @(posedge clk or negedge rst) begin
        if (!rst)
            j <= 1'b0;
        else
            j <= h;
    end

    // Second register
    always @(posedge clk or negedge rst) begin
        if (!rst)
            k <= 1'b0;
        else
            k <= i;
    end

    // Second AND-OR module
    com_and_or u2 (
        .a(k),
        .b(e),
        .c(f),
        .d(g),
        .e(l)
    );

    // Single-bit adder
    assign m = j + l;

    // Third register
    always @(posedge clk or negedge rst) begin
        if (!rst)
            n <= 1'b0;
        else
            n <= m;
    end

endmodule
