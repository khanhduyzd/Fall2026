
`timescale 1ns/1ps

module tb_com_and_or;

    reg a, b, c, d;
    wire e;

    reg expected;
    integer i;
    integer errors;

    com_and_or uut (
        .a(a),
        .b(b),
        .c(c),
        .d(d),
        .e(e)
    );

    initial begin
        {a, b, c, d} = 4'b0000;
        errors = 0;

        for (i = 0; i < 16; i = i + 1) begin
            {a, b, c, d} = i;
            #10;

            expected = (a & b) | c | d;

            if (e !== expected) begin
                $display("ERROR: abcd=%b%b%b%b e=%b",
                         a, b, c, d, e);
                errors = errors + 1;
            end
        end

        if (errors == 0)
            $display("PASS: All 16 combinations passed.");
        else
            $display("FAIL: %0d errors.", errors);

        $finish;
    end

endmodule
